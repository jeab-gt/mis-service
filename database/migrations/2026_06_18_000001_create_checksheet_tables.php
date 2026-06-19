<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. checksheet_templates
        Schema::create('checksheet_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->enum('frequency', ['realtime', 'hourly', 'daily', 'weekly', 'monthly'])->default('daily');
            $table->foreignId('flow_id')->nullable()->constrained('flows')->nullOnDelete();
            $table->enum('factory_scope', ['own_factory', 'all_factories'])->default('own_factory');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 2. checksheet_parameters
        Schema::create('checksheet_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('checksheet_templates')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('unit')->nullable();
            $table->enum('type', ['number', 'text', 'boolean', 'enum', 'pass_fail'])->default('number');
            $table->json('options')->nullable();
            $table->decimal('spec_min', 10, 4)->nullable();
            $table->decimal('spec_max', 10, 4)->nullable();
            $table->decimal('spec_target', 10, 4)->nullable();
            $table->enum('alert_on', ['above_max', 'below_min', 'both', 'none'])->default('both');
            $table->enum('alert_level', ['warning', 'critical'])->default('warning');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. checksheet_time_slots
        Schema::create('checksheet_time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('checksheet_templates')->cascadeOnDelete();
            $table->string('label');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 4. checksheet_records
        Schema::create('checksheet_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('checksheet_templates');
            $table->foreignId('factory_id')->constrained('masters');
            $table->date('record_date');
            $table->foreignId('time_slot_id')->nullable()->constrained('checksheet_time_slots')->nullOnDelete();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('current_node_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['template_id', 'factory_id', 'record_date']);
        });

        // 5. checksheet_record_values
        Schema::create('checksheet_record_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('checksheet_records')->cascadeOnDelete();
            $table->foreignId('parameter_id')->constrained('checksheet_parameters');
            $table->text('value')->nullable();
            $table->boolean('is_alert')->default(false);
            $table->enum('alert_level', ['warning', 'critical'])->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['record_id', 'parameter_id']);
            $table->index(['parameter_id', 'created_at']);
        });

        \DB::statement("ALTER TABLE checksheet_record_values COMMENT 'hot_table_2026'");

        // 6. checksheet_daily_summary
        Schema::create('checksheet_daily_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('checksheet_templates');
            $table->foreignId('factory_id')->constrained('masters');
            $table->foreignId('parameter_id')->constrained('checksheet_parameters');
            $table->date('summary_date');
            $table->decimal('avg_value', 10, 4)->nullable();
            $table->decimal('min_value', 10, 4)->nullable();
            $table->decimal('max_value', 10, 4)->nullable();
            $table->integer('total_count')->default(0);
            $table->integer('alert_count')->default(0);
            $table->timestamps();

            $table->unique(['template_id', 'factory_id', 'parameter_id', 'summary_date'], 'cs_daily_summary_unique');
            $table->index(['template_id', 'factory_id', 'summary_date'], 'cs_daily_summary_idx');
        });

        // 7. checksheet_archive_logs
        Schema::create('checksheet_archive_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('table_name');
            $table->integer('rows_archived');
            $table->foreignId('archived_by')->constrained('users');
            $table->timestamp('archived_at');
            $table->timestamps();
        });

        // 8. dashboards
        Schema::create('dashboards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('factory_scope', ['own_factory', 'specific', 'all'])->default('own_factory');
            $table->json('layout')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 9. dashboard_widgets
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->cascadeOnDelete();
            $table->enum('widget_type', ['line_chart', 'bar_chart', 'gauge', 'heatmap', 'kpi_card', 'data_table']);
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->json('config')->nullable();
            $table->integer('pos_x')->default(0);
            $table->integer('pos_y')->default(0);
            $table->integer('width')->default(6);
            $table->integer('height')->default(4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboards');
        Schema::dropIfExists('checksheet_archive_logs');
        Schema::dropIfExists('checksheet_daily_summary');
        Schema::dropIfExists('checksheet_record_values');
        Schema::dropIfExists('checksheet_records');
        Schema::dropIfExists('checksheet_time_slots');
        Schema::dropIfExists('checksheet_parameters');
        Schema::dropIfExists('checksheet_templates');
    }
};
