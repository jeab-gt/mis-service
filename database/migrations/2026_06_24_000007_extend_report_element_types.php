<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE project_report_elements MODIFY COLUMN `type` ENUM('text','image','chart','kpi','shape','gantt_mini','milestone_list','team_list','blocker_list','table','divider') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE project_report_elements MODIFY COLUMN `type` ENUM('text','image','chart','kpi','shape') NOT NULL");
    }
};
