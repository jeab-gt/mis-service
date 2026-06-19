<?php

namespace App\Http\Controllers;

use App\Models\ChecksheetDailySummary;
use App\Models\ChecksheetRecord;
use App\Models\DashboardWidget;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardWidgetController extends Controller
{
    public function data(DashboardWidget $widget): JsonResponse
    {
        $config = $widget->config ?? [];

        $templateId   = $config['template_id'] ?? null;
        $parameterIds = $config['parameter_ids'] ?? [];
        $dateRange    = $config['date_range'] ?? 'last_30_days';
        $factoryId    = $config['factory_id'] ?? null;

        [$dateFrom, $dateTo] = $this->resolveDateRange($dateRange);

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
                return $this->tableData($widget, $templateId, $factoryId, $dateFrom, $dateTo);

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

    private function chartData(DashboardWidget $widget, $templateId, $parameterIds, $factoryId, $dateFrom, $dateTo): JsonResponse
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

        $summaries = $query->with('parameter')->orderBy('summary_date')->get();

        $labels = [];
        $datasets = [];

        $grouped = $summaries->groupBy('parameter_id');

        foreach ($grouped as $paramId => $rows) {
            $paramName = $rows->first()->parameter?->name ?? "Param {$paramId}";
            $data = $rows->map(fn($r) => [
                'x' => $r->summary_date->toDateString(),
                'y' => $r->avg_value,
            ])->values();

            $dates = $rows->pluck('summary_date')->map(fn($d) => $d->toDateString())->unique()->values()->toArray();
            $labels = array_unique(array_merge($labels, $dates));

            $datasets[] = [
                'label' => $paramName,
                'data'  => $data,
            ];
        }

        sort($labels);

        return response()->json([
            'type'     => $widget->widget_type,
            'labels'   => $labels,
            'datasets' => $datasets,
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

    private function tableData(DashboardWidget $widget, $templateId, $factoryId, $dateFrom, $dateTo): JsonResponse
    {
        $query = ChecksheetRecord::whereBetween('record_date', [$dateFrom, $dateTo])
            ->with(['factory', 'timeSlot', 'submitter'])
            ->withCount(['values as alert_count' => fn($q) => $q->where('is_alert', true)])
            ->orderByDesc('record_date')
            ->limit(20);

        if ($templateId) $query->where('template_id', $templateId);
        if ($factoryId) $query->where('factory_id', $factoryId);

        $records = $query->get()->map(fn($r) => [
            'id'          => $r->id,
            'record_date' => $r->record_date->toDateString(),
            'time_slot'   => $r->timeSlot?->label,
            'factory'     => $r->factory?->name_th,
            'status'      => $r->status,
            'alert_count' => $r->alert_count,
            'submitted_by' => $r->submitter?->name,
        ]);

        return response()->json([
            'type'    => 'data_table',
            'records' => $records,
        ]);
    }
}
