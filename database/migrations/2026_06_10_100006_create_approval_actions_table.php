<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('step_id');
            $table->unsignedBigInteger('actor_id');
            $table->enum('action', ['approve', 'reject', 'delegate', 'comment']);
            $table->text('comment')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('app_submissions')->onDelete('cascade');
            $table->foreign('step_id')->references('id')->on('approval_steps')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
