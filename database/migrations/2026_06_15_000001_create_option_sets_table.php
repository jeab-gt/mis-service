<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_sets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_th');
            $table->string('name_en');
            $table->enum('source_type', ['static', 'master', 'users', 'roles'])->default('static');
            $table->string('master_type', 50)->nullable();
            $table->boolean('filter_by_factory')->default(false);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_sets');
    }
};
