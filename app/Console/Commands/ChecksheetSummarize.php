<?php

namespace App\Console\Commands;

use App\Models\ChecksheetDailySummary;
use App\Models\ChecksheetRecord;
use Illuminate\Console\Command;

class ChecksheetSummarize extends Command
{
    protected $signature = 'mis:checksheet-summarize
                            {date? : Date in Y-m-d format, defaults to yesterday}
                            {--all : Backfill every date that has submitted records (ignores date argument)}';

    protected $description = 'Summarize checksheet record values into daily summary table';

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->handleAll();
        }

        $dateInput = $this->argument('date');
        $date      = $dateInput ? \Carbon\Carbon::parse($dateInput)->toDateString() : now()->subDay()->toDateString();

        return $this->summarizeDate($date);
    }

    private function handleAll(): int
    {
        $dates = ChecksheetRecord::where('status', 'submitted')
            ->distinct()
            ->orderBy('record_date')
            ->pluck('record_date')
            ->map(fn($d) => $d instanceof \Carbon\Carbon ? $d->toDateString() : (string) $d)
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            $this->warn('No submitted records found in any date.');
            return self::SUCCESS;
        }

        $this->info("Backfilling {$dates->count()} date(s): {$dates->first()} → {$dates->last()}");

        foreach ($dates as $date) {
            $this->summarizeDate($date);
        }

        $this->info('All dates backfilled.');
        return self::SUCCESS;
    }

    private function summarizeDate(string $date): int
    {
        $this->info("Summarizing: {$date}");

        $records = ChecksheetRecord::where('record_date', $date)
            ->where('status', 'submitted')
            ->with(['values'])
            ->get();

        if ($records->isEmpty()) {
            $this->warn("  No submitted records for {$date}");
            return self::SUCCESS;
        }

        // Group all values by template+factory+parameter to aggregate across slots
        $buckets = [];
        foreach ($records as $record) {
            foreach ($record->values as $v) {
                $key = "{$record->template_id}|{$record->factory_id}|{$v->parameter_id}";
                $buckets[$key][] = $v;
            }
        }

        foreach ($buckets as $key => $values) {
            [$templateId, $factoryId, $parameterId] = explode('|', $key);
            $collect = collect($values);

            $numericValues = $collect->filter(fn($v) => is_numeric($v->value))->map(fn($v) => (float) $v->value);

            ChecksheetDailySummary::updateOrCreate(
                [
                    'template_id'  => $templateId,
                    'factory_id'   => $factoryId,
                    'parameter_id' => $parameterId,
                    'summary_date' => $date,
                ],
                [
                    'avg_value'   => $numericValues->isNotEmpty() ? $numericValues->average() : null,
                    'min_value'   => $numericValues->isNotEmpty() ? $numericValues->min() : null,
                    'max_value'   => $numericValues->isNotEmpty() ? $numericValues->max() : null,
                    'total_count' => $collect->count(),
                    'alert_count' => $collect->where('is_alert', true)->count(),
                ]
            );
        }

        $this->info("  Done — " . count($buckets) . " parameter buckets written.");
        return self::SUCCESS;
    }
}
