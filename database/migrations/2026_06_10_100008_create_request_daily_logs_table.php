<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_daily_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('user_id');
            $table->date('log_date');
            $table->tinyInteger('progress_pct');
            $table->text('detail');
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('app_submissions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_daily_logs');
    }
};
