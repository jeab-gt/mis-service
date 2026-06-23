<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('objective')->nullable();
            $table->unsignedBigInteger('submission_id')->nullable();
            $table->foreign('submission_id')->references('id')->on('app_submissions')->nullOnDelete();
            $table->unsignedBigInteger('factory_id');
            $table->foreign('factory_id')->references('id')->on('masters');
            $table->unsignedBigInteger('manager_id');
            $table->foreign('manager_id')->references('id')->on('users');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])->default('planning');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->decimal('budget', 15, 2)->nullable();
            $table->tinyInteger('progress_pct')->default(0);
            $table->string('color')->default('#6366f1');
            $table->boolean('is_cross_factory')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
