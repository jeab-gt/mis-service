<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_id');
            $table->integer('step_order');
            $table->string('name_th');
            $table->string('name_en');
            $table->unsignedBigInteger('approver_role_id');
            $table->enum('action_type', ['any_one', 'all_must'])->default('any_one');
            $table->integer('sla_hours')->nullable();
            $table->timestamps();

            $table->foreign('app_id')->references('id')->on('apps')->onDelete('cascade');
            $table->foreign('approver_role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
