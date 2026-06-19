<?php

namespace App\Services;

use App\Models\ChecksheetArchiveLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChecksheetArchiveService
{
    public function getHotTableStats(): array
    {
        $hotTable = 'checksheet_record_values';

        $count = DB::table($hotTable)->count();

        $yearRange = DB::table($hotTable)
            ->selectRaw('MIN(YEAR(created_at)) as min_year, MAX(YEAR(created_at)) as max_year')
            ->first();

        $sizeResult = DB::select("
            SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = ?
        ", [$hotTable]);

        $sizeMb = $sizeResult[0]->size_mb ?? 0;

        return [
            'table'     => $hotTable,
            'row_count' => $count,
            'min_year'  => $yearRange->min_year ?? null,
            'max_year'  => $yearRange->max_year ?? null,
            'size_mb'   => $sizeMb,
        ];
    }

    public function getArchiveTables(): array
    {
        $results = DB::select("SHOW TABLES LIKE 'checksheet_record_values_%'");
        $tableNames = array_map(fn($row) => array_values((array) $row)[0], $results);

        $stats = [];
        foreach ($tableNames as $tableName) {
            $count = DB::table($tableName)->count();

            $sizeResult = DB::select("
                SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                AND table_name = ?
            ", [$tableName]);

            $log = \App\Models\ChecksheetArchiveLog::where('table_name', $tableName)
                ->orderByDesc('archived_at')
                ->first();

            $stats[] = [
                'table'       => $tableName,
                'row_count'   => $count,
                'size_mb'     => $sizeResult[0]->size_mb ?? 0,
                'archived_at' => $log?->archived_at,
                'archived_by' => $log?->archivedBy?->name ?? null,
            ];
        }

        return $stats;
    }

    public function archiveYear(int $year, User $archivedBy): int
    {
        $hotTable     = 'checksheet_record_values';
        $archiveTable = "checksheet_record_values_{$year}";

        // Create archive table with same structure
        DB::statement("CREATE TABLE IF NOT EXISTS `{$archiveTable}` LIKE `{$hotTable}`");

        // Insert rows for the given year
        $rowCount = DB::statement("
            INSERT INTO `{$archiveTable}`
            SELECT * FROM `{$hotTable}`
            WHERE YEAR(created_at) = {$year}
        ");

        // Count what was inserted
        $inserted = DB::table($archiveTable)->whereRaw("YEAR(created_at) = {$year}")->count();

        // Delete from hot table
        DB::table($hotTable)->whereRaw("YEAR(created_at) = {$year}")->delete();

        // Log the archive
        \App\Models\ChecksheetArchiveLog::create([
            'year'          => $year,
            'table_name'    => $archiveTable,
            'rows_archived' => $inserted,
            'archived_by'   => $archivedBy->id,
            'archived_at'   => now(),
        ]);

        return $inserted;
    }

    public function dropArchive(string $tableName, User $user): void
    {
        if (!str_starts_with($tableName, 'checksheet_record_values_')) {
            throw new \InvalidArgumentException("Invalid archive table name: {$tableName}");
        }

        Schema::dropIfExists($tableName);
    }
}
