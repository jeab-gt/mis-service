<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_report_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slide_id')->constrained('project_report_slides')->cascadeOnDelete();
            $table->enum('type', ['text', 'image', 'chart', 'kpi', 'shape']);
            $table->decimal('x', 8, 2)->default(0);
            $table->decimal('y', 8, 2)->default(0);
            $table->decimal('w', 8, 2)->default(200);
            $table->decimal('h', 8, 2)->default(100);
            $table->unsignedSmallInteger('z_index')->default(0);
            $table->json('props');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_report_elements');
    }
};
