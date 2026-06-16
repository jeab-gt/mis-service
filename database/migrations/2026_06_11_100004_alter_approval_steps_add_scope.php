<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_steps', function (Blueprint $table) {
            $table->enum('scope', ['own_factory', 'parent_factory', 'any_factory'])
                ->default('own_factory')
                ->after('sla_hours')
                ->comment('Scope of valid approvers: own_factory=same factory, parent_factory=HQ only, any_factory=any');
        });
    }

    public function down(): void
    {
        Schema::table('approval_steps', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};
