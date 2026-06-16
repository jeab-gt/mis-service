<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('section_id')->nullable()->after('id');
            $table->string('employee_code')->nullable()->after('section_id');
            $table->string('name_th')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_th');
            $table->string('phone')->nullable()->after('name_en');
            $table->boolean('is_active')->default(true)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['section_id', 'employee_code', 'name_th', 'name_en', 'phone', 'is_active']);
        });
    }
};
