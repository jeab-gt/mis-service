<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_report_slides', function (Blueprint $table) {
            $table->json('widgets_data')->nullable()->after('html_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_report_slides', function (Blueprint $table) {
            $table->dropColumn('widgets_data');
        });
    }
};
