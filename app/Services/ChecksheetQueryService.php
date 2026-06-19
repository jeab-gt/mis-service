<?php

namespace App\Services;

use App\Models\ChecksheetDailySummary;
use App\Models\ChecksheetRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChecksheetQueryService
{
    public function getHotTable(): string
    {
        return 'checksheet_record_values';
    }

    public function getArchiveTables(): array
    {
        $results = DB::select("SHOW TABLES LIKE 'checksheet_record_values_%'");

        return array_map(function ($row) {
            return array_values((array) $row)[0];
        }, $results);
    }

    public function queryRange(
        int $templateId,
        int $factoryId,
        string $dateFrom,
        string $dateTo,
        array $parameterIds = []
    ): Collection {
        $hotTable = $this->getHotTable();
        $archiveTables = $this->getArchiveTables();

        $tables = array_merge([$hotTable], $archiveTables);

        $unions = [];
        $bindings = [];

        foreach ($tables as $table) {
            $sql = "SELECT v.id, v.record_id, v.parameter_id, v.value, v.is_alert, v.alert_level, v.recorded_by, v.created_at,
                           r.template_id, r.factory_id, r.record_date, r.time_slot_id, r.status
                    FROM `{$table}` v
                    INNER JOIN checksheet_records r ON r.id = v.record_id
                    WHERE r.template_id = ? AND r.factory_id = ? AND r.record_date BETWEEN ? AND ?";

            $bind = [$templateId, $factoryId, $dateFrom, $dateTo];

            if (!empty($parameterIds)) {
                $placeholders = implode(',', array_fill(0, count($parameterIds), '?'));
                $sql .= " AND v.parameter_id IN ({$placeholders})";
                $bind = array_merge($bind, $parameterIds);
            }

            $unions[] = $sql;
            $bindings = array_merge($bindings, $bind);
        }

        $fullSql = implode(' UNION ALL ', $unions);
        $rows = DB::select($fullSql, $bindings);

        return collect($rows);
    }

    public function summarize(ChecksheetRecord $record): void
    {
        $values = $record->values()->with('parameter')->get();

        $grouped = $values->groupBy('parameter_id');

        foreach ($grouped as $parameterId => $paramValues) {
            $parameter = $paramValues->first()->parameter;

            $numericValues = $paramValues
                ->filter(fn($v) => is_numeric($v->value))
                ->map(fn($v) => (float) $v->value);

            $totalCount  = $paramValues->count();
            $alertCount  = $paramValues->where('is_alert', true)->count();

            $avgValue = $numericValues->isNotEmpty() ? $numericValues->average() : null;
            $minValue = $numericValues->isNotEmpty() ? $numericValues->min() : null;
            $maxValue = $numericValues->isNotEmpty() ? $numericValues->max() : null;

            ChecksheetDailySummary::updateOrCreate(
                [
                    'template_id'  => $record->template_id,
                    'factory_id'   => $record->factory_id,
                    'parameter_id' => $parameterId,
                    'summary_date' => $record->record_date->toDateString(),
                ],
                [
                    'avg_value'   => $avgValue,
                    'min_value'   => $minValue,
                    'max_value'   => $maxValue,
                    'total_count' => $totalCount,
                    'alert_count' => $alertCount,
                ]
            );
        }
    }
}
