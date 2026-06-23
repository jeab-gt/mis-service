<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_task_id')->nullable();
            $table->unsignedBigInteger('milestone_id')->nullable();
            $table->foreign('milestone_id')->references('id')->on('project_milestones')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->foreign('assignee_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->decimal('actual_hours', 6, 2)->default(0);
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['todo', 'in_progress', 'review', 'done', 'cancelled'])->default('todo');
            $table->tinyInteger('progress_pct')->default(0);
            $table->integer('sort_order')->default(0);
            $table->json('labels')->nullable();
            $table->timestamps();
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->foreign('parent_task_id')->references('id')->on('project_tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
