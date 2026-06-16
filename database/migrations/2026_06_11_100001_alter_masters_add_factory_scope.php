<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add company & team to the type enum (keep existing values)
        DB::statement("ALTER TABLE masters MODIFY COLUMN `type` ENUM('company','factory','plant','department','section','team','line') NOT NULL");

        Schema::table('masters', function (Blueprint $table) {
            $table->unsignedBigInteger('factory_id')->nullable()->after('parent_id')
                ->comment('ID of the factory-level ancestor for scoping');
            $table->foreign('factory_id')->references('id')->on('masters')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->dropForeign(['factory_id']);
            $table->dropColumn('factory_id');
        });
        DB::statement("ALTER TABLE masters MODIFY COLUMN `type` ENUM('factory','plant','department','section','line') NOT NULL");
    }
};
