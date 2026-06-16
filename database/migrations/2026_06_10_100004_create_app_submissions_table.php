<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_id');
            $table->unsignedBigInteger('submitter_id');
            $table->json('form_data');
            $table->integer('current_step')->default(0);
            $table->enum('status', ['draft', 'submitted', 'in_review', 'approved', 'rejected', 'closed'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('app_id')->references('id')->on('apps')->onDelete('cascade');
            $table->foreign('submitter_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_submissions');
    }
};
