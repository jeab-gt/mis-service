<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE approval_actions DROP FOREIGN KEY approval_actions_step_id_foreign');
        } catch (\Exception $e) {
            // constraint may have a different name; proceed
        }
        DB::statement('ALTER TABLE approval_actions MODIFY COLUMN step_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE approval_actions ADD COLUMN node_id VARCHAR(100) NULL AFTER step_id');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE approval_actions MODIFY COLUMN step_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE approval_actions DROP COLUMN node_id');
        DB::statement('ALTER TABLE approval_actions ADD CONSTRAINT approval_actions_step_id_foreign FOREIGN KEY (step_id) REFERENCES approval_steps(id) ON DELETE CASCADE');
    }
};
