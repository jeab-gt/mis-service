<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')
                  ->constrained('app_categories')->nullOnDelete();
            $table->enum('app_type', ['form', 'checksheet'])->default('form')->after('category_id');
            $table->foreignId('dashboard_id')->nullable()->after('app_type')
                  ->constrained('dashboards')->nullOnDelete();
            $table->json('allowed_roles')->nullable()->after('dashboard_id');
            $table->json('allowed_factories')->nullable()->after('allowed_roles');
        });
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['dashboard_id']);
            $table->dropColumn(['category_id', 'app_type', 'dashboard_id', 'allowed_roles', 'allowed_factories']);
        });
    }
};
