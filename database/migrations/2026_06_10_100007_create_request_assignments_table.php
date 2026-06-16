<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('assignee_id');
            $table->unsignedBigInteger('assigned_by');
            $table->date('due_date')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('app_submissions')->onDelete('cascade');
            $table->foreign('assignee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_assignments');
    }
};
