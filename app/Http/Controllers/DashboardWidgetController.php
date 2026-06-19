<?php

namespace App\Http\Controllers;

use App\Models\ChecksheetDailySummary;
use App\Models\ChecksheetRecord;
use App\Models\ChecksheetRecordValue;
use App\Models\DashboardWidget;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardWidgetController extends Controller
{
    public function data(DashboardWidget $widget): JsonResponse
    {
        $config = $widget->config ?? [];

        $templateId   = $config['template_id'] ?? null;
        $parameterIds = $config['parameter_ids'] ?? [];
        $dateRange    = $config['date_range'] ?? 'last_30_days';
        $factoryId    = $config['factory_id'] ?? null;

        // Query params override widget config's date range (dashboard-level filter)
        if (request()->filled('date_from') && request()->filled('date_to')) {
            $dateFrom = request('date_from');
            $dateTo   = request('date_to');
        } else {
            [$dateFrom, $dateTo] = $this->resolveDateRange($dateRange);
        }

        switch ($widget->widget_type) {
            case 'line_chart':
            case 'bar_chart':
                return $this->chartData($widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo);

            case 'kpi_card':
                return $this->kpiData($widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo);

            case 'gauge':
                return $this->gaugeData($widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo);

            case 'heatmap':
                return $this->heatmapData($widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo);

            case 'data_table':
                return $this->tableData($widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo);

            default:
                return response()->json(['error' => 'Unknown widget type']);
        }
    }

    private function resolveDateRange(string $dateRange): array
    {
        $now = Carbon::today();
        return match ($dateRange) {
            'last_7_days'  => [$now->copy()->subDays(6)->toDateString(), $now->toDateString()],
            'last_30_days' => [$now->copy()->subDays(29)->toDateString(), $now->toDateString()],
            'this_month'   => [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()],
            'last_month'   => [$now->copy()->subMonth()->startOfMonth()->toDateString(), $now->copy()->subMonth()->endOfMonth()->toDateString()],
            default        => [$now->copy()->subDays(29)->toDateString(), $now->toDateString()],
        };
    }

    private function resolveSpec($templateId, array $parameterIds): array
    {
        // Spec lines only make sense for a single parameter
        $paramId = count($parameterIds) === 1 ? $parameterIds[0] : null;

        if (!$paramId && $templateId) {
            $params = \App\Models\ChecksheetParameter::where('template_id', $templateId)
                ->where('is_active', true)->get();
            if ($params->count() === 1) {
                $paramId = $params->first()->id;
            }
        }

        if (!$paramId) return ['min' => null, 'max' => null, 'target' => null];

        $param = \App\Models\ChecksheetParameter::find($paramId);
        return [
            'min'    => $param?->spec_min    !== null ? (float) $param->spec_min    : null,
            'max'    => $param?->spec_max    !== null ? (float) $param->spec_max    : null,
            'target' => $param?->spec_target !== null ? (float) $param->spec_target : null,
        ];
    }

    private function chartData(DashboardWidget $widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo): JsonResponse
    {
        $query = ChecksheetDailySummary::whereBetween('summary_date', [$dateFrom, $dateTo]);

        if ($templateId) $query->where('template_id', $templateId);
        if (!empty($parameterIds)) $query->whereIn('parameter_id', $parameterIds);
        if ($factoryId) $query->where('factory_id', $factoryId);

        $summaries = $query->with('parameter')->orderBy('summary_date')->get();

        // Fallback to raw record values when daily summary hasn't been generated yet
        if ($summaries->isEmpty()) {
            return $this->chartDataFromRaw($widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo);
        }

        $labels   = [];
        $datasets = [];
        $grouped  = $summaries->groupBy('parameter_id');

        foreach ($grouped as $paramId => $rows) {
            $paramName = $rows->first()->parameter?->name ?? "Param {$paramId}";
            $data = $rows->map(fn($r) => [
                'x' => $r->summary_date->toDateString(),
                'y' => $r->avg_value,
            ])->values();

            $dates  = $rows->pluck('summary_date')->map(fn($d) => $d->toDateString())->unique()->values()->toArray();
            $labels = array_unique(array_merge($labels, $dates));

            $datasets[] = ['label' => $paramName, 'data' => $data];
        }

        sort($labels);

        $config = $widget->config ?? [];

        return response()->json([
            'type'            => $widget->widget_type,
            'labels'          => $labels,
            'datasets'        => $datasets,
            'spec'            => $this->resolveSpec($templateId, $parameterIds),
            'show_spec_lines' => (bool) ($config['show_spec_lines'] ?? false),
        ]);
    }

    private function chartDataFromRaw(DashboardWidget $widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo): JsonResponse
    {
        $query = ChecksheetRecordValue::query()
            ->join('checksheet_records as cr', 'checksheet_record_values.record_id', '=', 'cr.id')
            ->join('checksheet_parameters as cp', 'checksheet_record_values.parameter_id', '=', 'cp.id')
            ->select(
                DB::raw('DATE(cr.record_date) as summary_date'),
                'checksheet_record_values.parameter_id',
                'cp.name as parameter_name',
                DB::raw("AVG(CASE WHEN checksheet_record_values.value REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                              THEN CAST(checksheet_record_values.value AS DECIMAL(12,4))
                              ELSE NULL END) as avg_value")
            )
            ->whereIn('cr.status', ['submitted', 'approved'])
            ->whereBetween('cr.record_date', [$dateFrom, $dateTo])
            ->groupBy('cr.record_date', 'checksheet_record_values.parameter_id', 'cp.name')
            ->orderBy('cr.record_date');

        if ($templateId) $query->where('cr.template_id', $templateId);
        if (!empty($parameterIds)) $query->whereIn('checksheet_record_values.parameter_id', $parameterIds);
        if ($factoryId) $query->where('cr.factory_id', $factoryId);

        $rows    = $query->get();
        $labels  = [];
        $datasets = [];
        $grouped = $rows->groupBy('parameter_id');

        foreach ($grouped as $paramId => $paramRows) {
            $paramName = $paramRows->first()->parameter_name ?? "Param {$paramId}";
            $data = $paramRows->filter(fn($r) => $r->avg_value !== null)->map(fn($r) => [
                'x' => $r->summary_date,
                'y' => round((float) $r->avg_value, 2),
            ])->values();

            $dates  = $paramRows->pluck('summary_date')->unique()->values()->toArray();
            $labels = array_unique(array_merge($labels, $dates));

            $datasets[] = ['label' => $paramName, 'data' => $data];
        }

        sort($labels);

        $config = $widget->config ?? [];

        return response()->json([
            'type'            => $widget->widget_type,
            'labels'          => $labels,
            'datasets'        => $datasets,
            'spec'            => $this->resolveSpec($templateId, $parameterIds),
            'show_spec_lines' => (bool) ($config['show_spec_lines'] ?? false),
        ]);
    }

    private function kpiData(DashboardWidget $widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo): JsonResponse
    {
        $query = ChecksheetDailySummary::whereBetween('summary_date', [$dateFrom, $dateTo]);

        if ($templateId) {
            $query->where('template_id', $templateId);
        }
        if (!empty($parameterIds)) {
            $query->whereIn('parameter_id', $parameterIds);
        }
        if ($factoryId) {
            $query->where('factory_id', $factoryId);
        }

        $summary = $query->selectRaw('
            AVG(avg_value) as overall_avg,
            MIN(min_value) as overall_min,
            MAX(max_value) as overall_max,
            SUM(alert_count) as total_alerts,
            SUM(total_count) as total_records
        ')->first();

        // Last value
        $latest = $query->orderByDesc('summary_date')->first();

        return response()->json([
            'type'          => 'kpi_card',
            'latest_value'  => $latest?->avg_value,
            'avg'           => $summary?->overall_avg,
            'min'           => $summary?->overall_min,
            'max'           => $summary?->overall_max,
            'total_alerts'  => $summary?->total_alerts ?? 0,
            'total_records' => $summary?->total_records ?? 0,
        ]);
    }

    private function gaugeData(DashboardWidget $widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo): JsonResponse
    {
        $query = ChecksheetDailySummary::whereBetween('summary_date', [$dateFrom, $dateTo]);

        if ($templateId) $query->where('template_id', $templateId);
        if (!empty($parameterIds)) $query->whereIn('parameter_id', $parameterIds);
        if ($factoryId) $query->where('factory_id', $factoryId);

        $latest = $query->orderByDesc('summary_date')->first();
        $param  = !empty($parameterIds) ? \App\Models\ChecksheetParameter::find($parameterIds[0]) : null;

        return response()->json([
            'type'        => 'gauge',
            'value'       => $latest?->avg_value ?? 0,
            'min'         => $param?->spec_min ?? 0,
            'max'         => $param?->spec_max ?? 100,
            'target'      => $param?->spec_target ?? null,
            'alert_level' => $latest && ($latest->alert_count ?? 0) > 0 ? 'warning' : null,
        ]);
    }

    private function heatmapData(DashboardWidget $widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo): JsonResponse
    {
        $query = ChecksheetDailySummary::whereBetween('summary_date', [$dateFrom, $dateTo]);

        if ($templateId) $query->where('template_id', $templateId);
        if (!empty($parameterIds)) $query->whereIn('parameter_id', $parameterIds);
        if ($factoryId) $query->where('factory_id', $factoryId);

        $rows = $query->with('parameter')->orderBy('summary_date')->orderBy('parameter_id')->get();

        $dates      = $rows->pluck('summary_date')->map(fn($d) => $d->toDateString())->unique()->values()->toArray();
        $parameters = $rows->pluck('parameter.name', 'parameter_id')->unique()->toArray();

        $cells = [];
        foreach ($rows as $row) {
            $cells[$row->summary_date->toDateString()][$row->parameter_id] = [
                'alert_count' => $row->alert_count,
                'total_count' => $row->total_count,
                'level'       => $row->alert_count > 0 ? ($row->alert_count >= $row->total_count * 0.5 ? 'critical' : 'warning') : 'ok',
            ];
        }

        return response()->json([
            'type'       => 'heatmap',
            'dates'      => $dates,
            'parameters' => $parameters,
            'cells'      => $cells,
        ]);
    }

    private function tableData(DashboardWidget $widget, $templateId, array $parameterIds, $factoryId, $dateFrom, $dateTo): JsonResponse
    {
        // Ordered parameter columns (filter by selected IDs if specified)
        $paramQuery = \App\Models\ChecksheetParameter::where('is_active', true)->orderBy('sort_order');
        if ($templateId) $paramQuery->where('template_id', $templateId);
        if (!empty($parameterIds)) $paramQuery->whereIn('id', $parameterIds);
        $parameters = $paramQuery->get();

        $columns = $parameters->map(fn($p) => [
            'id'   => $p->id,
            'name' => $p->name,
            'unit' => $p->unit,
            'type' => $p->type,
        ])->values();

        $query = ChecksheetRecord::whereBetween('record_date', [$dateFrom, $dateTo])
            ->with(['factory', 'timeSlot', 'values'])
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->limit(20);

        if ($templateId) $query->where('template_id', $templateId);
        if ($factoryId) $query->where('factory_id', $factoryId);

        $paramIdSet = !empty($parameterIds) ? array_flip($parameterIds) : null;

        $records = $query->get()->map(function ($r) use ($paramIdSet) {
            $valuesMap = [];
            foreach ($r->values as $v) {
                if ($paramIdSet !== null && !isset($paramIdSet[$v->parameter_id])) continue;
                $valuesMap[$v->parameter_id] = [
                    'value'       => $v->value,
                    'is_alert'    => $v->is_alert,
                    'alert_level' => $v->alert_level,
                ];
            }
            return [
                'id'          => $r->id,
                'record_date' => $r->record_date->toDateString(),
                'time_slot'   => $r->timeSlot?->label,
                'factory'     => $r->factory?->name_th,
                'status'      => $r->status,
                'values'      => $valuesMap,
            ];
        });

        return response()->json([
            'type'    => 'data_table',
            'columns' => $columns,
            'records' => $records,
        ]);
    }
}
