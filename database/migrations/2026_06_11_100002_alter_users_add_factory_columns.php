<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('factory_id')->nullable()->after('section_id');
            $table->boolean('is_parent_factory')->default(false)->after('factory_id');
            $table->foreign('factory_id')->references('id')->on('masters')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['factory_id']);
            $table->dropColumn(['factory_id', 'is_parent_factory']);
        });
    }
};
