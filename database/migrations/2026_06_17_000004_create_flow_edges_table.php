<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('flows')->cascadeOnDelete();
            $table->string('from_node_id');
            $table->string('to_node_id');
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_edges');
    }
};
