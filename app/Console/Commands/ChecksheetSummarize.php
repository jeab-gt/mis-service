<?php

namespace App\Console\Commands;

use App\Models\ChecksheetDailySummary;
use App\Models\ChecksheetRecord;
use Illuminate\Console\Command;

class ChecksheetSummarize extends Command
{
    protected $signature = 'mis:checksheet-summarize {date? : Date in Y-m-d format, default yesterday}';

    protected $description = 'Summarize checksheet record values into daily summary table';

    public function handle(): int
    {
        $dateInput = $this->argument('date');

        if ($dateInput) {
            $date = \Carbon\Carbon::parse($dateInput)->toDateString();
        } else {
            $date = now()->subDay()->toDateString();
        }

        $this->info("Summarizing checksheet records for date: {$date}");

        $records = ChecksheetRecord::where('record_date', $date)
            ->where('status', 'submitted')
            ->with(['values.parameter'])
            ->get();

        if ($records->isEmpty()) {
            $this->warn("No submitted records found for {$date}");
            return self::SUCCESS;
        }

        $this->info("Found {$records->count()} records to summarize.");

        foreach ($records as $record) {
            $grouped = $record->values->groupBy('parameter_id');

            foreach ($grouped as $parameterId => $paramValues) {
                $numericValues = $paramValues
                    ->filter(fn($v) => is_numeric($v->value))
                    ->map(fn($v) => (float) $v->value);

                $totalCount = $paramValues->count();
                $alertCount = $paramValues->where('is_alert', true)->count();

                $avgValue = $numericValues->isNotEmpty() ? $numericValues->average() : null;
                $minValue = $numericValues->isNotEmpty() ? $numericValues->min() : null;
                $maxValue = $numericValues->isNotEmpty() ? $numericValues->max() : null;

                ChecksheetDailySummary::updateOrCreate(
                    [
                        'template_id'  => $record->template_id,
                        'factory_id'   => $record->factory_id,
                        'parameter_id' => $parameterId,
                        'summary_date' => $date,
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

        $this->info('Summarization complete.');

        return self::SUCCESS;
    }
}
