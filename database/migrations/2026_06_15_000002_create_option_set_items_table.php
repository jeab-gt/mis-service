<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_set_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_set_id')->constrained()->onDelete('cascade');
            $table->string('value', 100);
            $table->string('label_th');
            $table->string('label_en');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_set_items');
    }
};
