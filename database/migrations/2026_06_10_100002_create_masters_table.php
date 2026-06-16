<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->enum('type', ['factory', 'plant', 'department', 'section', 'line']);
            $table->string('code');
            $table->string('name_th');
            $table->string('name_en');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('masters')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masters');
    }
};
