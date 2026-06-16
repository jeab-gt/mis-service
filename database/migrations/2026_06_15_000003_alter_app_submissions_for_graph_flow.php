<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE app_submissions MODIFY COLUMN current_step VARCHAR(100) NULL DEFAULT NULL');
        DB::statement("ALTER TABLE app_submissions MODIFY COLUMN status ENUM('draft','submitted','in_review','approved','rejected','closed','returned') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE app_submissions MODIFY COLUMN current_step INT NOT NULL DEFAULT 0');
        DB::statement("ALTER TABLE app_submissions MODIFY COLUMN status ENUM('draft','submitted','in_review','approved','rejected','closed') NOT NULL DEFAULT 'draft'");
    }
};
