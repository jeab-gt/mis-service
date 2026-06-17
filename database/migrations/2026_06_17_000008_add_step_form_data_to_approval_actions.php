<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_actions', function (Blueprint $table) {
            $table->json('step_form_data')->nullable()->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('approval_actions', function (Blueprint $table) {
            $table->dropColumn('step_form_data');
        });
    }
};
