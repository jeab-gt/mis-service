<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('approval_actions', 'step_id')) {
            try {
                Schema::table('approval_actions', function (Blueprint $table) {
                    $table->dropForeign(['step_id']);
                });
            } catch (\Throwable $e) {
                // FK may have been dropped in a prior migration; ignore
            }
            Schema::table('approval_actions', function (Blueprint $table) {
                $table->dropColumn('step_id');
            });
        }

        Schema::dropIfExists('approval_steps');
    }

    public function down(): void
    {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::table('approval_actions', function (Blueprint $table) {
            $table->unsignedBigInteger('step_id')->nullable()->after('submission_id');
        });
    }
};
