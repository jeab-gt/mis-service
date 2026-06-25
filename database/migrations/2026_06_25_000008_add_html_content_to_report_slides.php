<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_report_slides', function (Blueprint $table) {
            $table->longText('html_content')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('project_report_slides', function (Blueprint $table) {
            $table->dropColumn('html_content');
        });
    }
};
