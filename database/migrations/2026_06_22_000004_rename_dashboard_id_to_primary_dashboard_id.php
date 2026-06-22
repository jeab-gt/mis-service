<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropForeign(['dashboard_id']);
            $table->renameColumn('dashboard_id', 'primary_dashboard_id');
        });
        Schema::table('apps', function (Blueprint $table) {
            $table->foreign('primary_dashboard_id')
                  ->references('id')->on('dashboards')
                  ->nullOnDelete();
        });

        Schema::table('checksheet_templates', function (Blueprint $table) {
            $table->dropForeign(['dashboard_id']);
            $table->renameColumn('dashboard_id', 'primary_dashboard_id');
        });
        Schema::table('checksheet_templates', function (Blueprint $table) {
            $table->foreign('primary_dashboard_id')
                  ->references('id')->on('dashboards')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropForeign(['primary_dashboard_id']);
            $table->renameColumn('primary_dashboard_id', 'dashboard_id');
        });
        Schema::table('apps', function (Blueprint $table) {
            $table->foreign('dashboard_id')->references('id')->on('dashboards')->nullOnDelete();
        });

        Schema::table('checksheet_templates', function (Blueprint $table) {
            $table->dropForeign(['primary_dashboard_id']);
            $table->renameColumn('primary_dashboard_id', 'dashboard_id');
        });
        Schema::table('checksheet_templates', function (Blueprint $table) {
            $table->foreign('dashboard_id')->references('id')->on('dashboards')->nullOnDelete();
        });
    }
};
