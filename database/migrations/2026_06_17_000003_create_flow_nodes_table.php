<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('flows')->cascadeOnDelete();
            $table->string('node_id');
            $table->enum('type', ['start', 'approval', 'end_approved', 'end_rejected', 'return_revision']);
            $table->string('name_th')->nullable();
            $table->string('name_en')->nullable();
            $table->enum('approver_source', ['role', 'specific_user', 'option_set'])->nullable();
            $table->unsignedBigInteger('approver_role_id')->nullable();
            $table->unsignedBigInteger('approver_user_id')->nullable();
            $table->string('approver_option_set_code')->nullable();
            $table->enum('scope', ['own_factory', 'parent_factory', 'any_factory'])->default('own_factory');
            $table->enum('action_type', ['any_one', 'all_must'])->default('any_one');
            $table->unsignedInteger('sla_hours')->nullable();
            $table->unsignedBigInteger('step_form_template_id')->nullable();
            $table->integer('pos_x')->default(0);
            $table->integer('pos_y')->default(0);
            $table->timestamps();

            $table->foreign('approver_role_id')->references('id')->on('roles')->nullOnDelete();
            $table->foreign('approver_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('step_form_template_id')->references('id')->on('form_templates')->nullOnDelete();
            $table->unique(['flow_id', 'node_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_nodes');
    }
};
