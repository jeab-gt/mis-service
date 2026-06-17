<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn(['form_schema', 'flow_schema']);
            $table->unsignedBigInteger('initial_form_template_id')->nullable()->after('icon');
            $table->unsignedBigInteger('revision_form_template_id')->nullable()->after('initial_form_template_id');
            $table->unsignedBigInteger('flow_id')->nullable()->after('revision_form_template_id');

            $table->foreign('initial_form_template_id')->references('id')->on('form_templates')->nullOnDelete();
            $table->foreign('revision_form_template_id')->references('id')->on('form_templates')->nullOnDelete();
            $table->foreign('flow_id')->references('id')->on('flows')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropForeign(['initial_form_template_id']);
            $table->dropForeign(['revision_form_template_id']);
            $table->dropForeign(['flow_id']);
            $table->dropColumn(['initial_form_template_id', 'revision_form_template_id', 'flow_id']);
            $table->json('form_schema')->nullable();
            $table->json('flow_schema')->nullable();
        });
    }
};
