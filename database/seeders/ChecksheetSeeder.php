<?php
namespace Database\Seeders;

use App\Models\ChecksheetTemplate;
use App\Models\ChecksheetParameter;
use App\Models\ChecksheetTimeSlot;
use App\Models\ChecksheetRecord;
use App\Models\ChecksheetRecordValue;
use App\Models\ChecksheetDailySummary;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Master;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChecksheetSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create factory
        $factory = Master::where('type', 'factory')->where('is_active', true)->first();
        if (!$factory) {
            // Try to find any master to use as factory
            $factory = Master::first();
        }
        if (!$factory) {
            $this->command->warn('No factory master found, skipping record seeding');
            return;
        }

        $user = User::first();
        if (!$user) {
            $this->command->warn('No user found');
            return;
        }

        // Template 1: Temperature monitoring
        $t1 = ChecksheetTemplate::firstOrCreate(
            ['slug' => 'temp-line-a'],
            [
                'name'          => 'บันทึกอุณหภูมิ Line A',
                'description'   => 'บันทึกอุณหภูมิและค่าไฟฟ้า Line A',
                'frequency'     => 'daily',
                'factory_scope' => 'own_factory',
                'is_active'     => true,
                'created_by'    => $user->id,
            ]
        );

        $params1 = [
            ['name' => 'อุณหภูมิ Motor',   'unit' => '°C',  'type' => 'number', 'spec_min' => 0,   'spec_max' => 85,  'spec_target' => 60, 'alert_on' => 'above_max', 'alert_level' => 'critical'],
            ['name' => 'อุณหภูมิ Ambient', 'unit' => '°C',  'type' => 'number', 'spec_min' => 15,  'spec_max' => 45,  'spec_target' => 30, 'alert_on' => 'both',      'alert_level' => 'warning'],
            ['name' => 'แรงดันไฟ',         'unit' => 'V',   'type' => 'number', 'spec_min' => 209, 'spec_max' => 231, 'spec_target' => 220,'alert_on' => 'both',      'alert_level' => 'warning'],
            ['name' => 'เสียงผิดปกติ',     'unit' => '-',   'type' => 'pass_fail', 'spec_min' => null, 'spec_max' => null, 'spec_target' => null, 'alert_on' => 'none', 'alert_level' => 'warning'],
            ['name' => 'หมายเหตุ',          'unit' => '-',   'type' => 'text',    'spec_min' => null, 'spec_max' => null, 'spec_target' => null, 'alert_on' => 'none', 'alert_level' => 'warning'],
        ];

        $createdParams1 = [];
        if ($t1->parameters()->count() === 0) {
            foreach ($params1 as $i => $p) {
                $param = $t1->parameters()->create([
                    'name'        => $p['name'],
                    'slug'        => Str::slug($p['name'] . '-' . ($i + 1)),
                    'unit'        => $p['unit'],
                    'type'        => $p['type'],
                    'spec_min'    => $p['spec_min'],
                    'spec_max'    => $p['spec_max'],
                    'spec_target' => $p['spec_target'],
                    'alert_on'    => $p['alert_on'],
                    'alert_level' => $p['alert_level'],
                    'sort_order'  => $i,
                    'is_active'   => true,
                ]);
                $createdParams1[] = $param;
            }
        } else {
            $createdParams1 = $t1->parameters()->get()->all();
        }

        $slots1 = ['08:00', '12:00', '16:00'];
        $createdSlots1 = [];
        if ($t1->timeSlots()->count() === 0) {
            foreach ($slots1 as $i => $label) {
                $createdSlots1[] = $t1->timeSlots()->create(['label' => $label, 'sort_order' => $i]);
            }
        } else {
            $createdSlots1 = $t1->timeSlots()->get()->all();
        }

        // Template 2: Quality Check
        $t2 = ChecksheetTemplate::firstOrCreate(
            ['slug' => 'quality-check'],
            [
                'name'          => 'Quality Check Daily',
                'description'   => 'Quality check ประจำวัน',
                'frequency'     => 'daily',
                'factory_scope' => 'own_factory',
                'is_active'     => true,
                'created_by'    => $user->id,
            ]
        );

        $params2 = [
            ['name' => 'Visual Inspection', 'unit' => '-',   'type' => 'pass_fail', 'spec_min' => null, 'spec_max' => null, 'spec_target' => null, 'alert_on' => 'none', 'alert_level' => 'warning'],
            ['name' => 'Dimension A',        'unit' => 'mm',  'type' => 'number',    'spec_min' => 9.95, 'spec_max' => 10.05,'spec_target' => 10.0, 'alert_on' => 'both', 'alert_level' => 'critical'],
            ['name' => 'Weight',             'unit' => 'g',   'type' => 'number',    'spec_min' => 99,   'spec_max' => 101,  'spec_target' => 100,  'alert_on' => 'both', 'alert_level' => 'warning'],
            ['name' => 'จำนวนผลิต',          'unit' => 'pcs', 'type' => 'number',    'spec_min' => 500,  'spec_max' => null, 'spec_target' => null, 'alert_on' => 'below_min', 'alert_level' => 'warning'],
            ['name' => 'NG Count',           'unit' => 'pcs', 'type' => 'number',    'spec_min' => null, 'spec_max' => null, 'spec_target' => null, 'alert_on' => 'none', 'alert_level' => 'warning'],
        ];

        $createdParams2 = [];
        if ($t2->parameters()->count() === 0) {
            foreach ($params2 as $i => $p) {
                $param = $t2->parameters()->create([
                    'name'        => $p['name'],
                    'slug'        => Str::slug($p['name'] . '-' . ($i + 1)),
                    'unit'        => $p['unit'],
                    'type'        => $p['type'],
                    'spec_min'    => $p['spec_min'],
                    'spec_max'    => $p['spec_max'],
                    'spec_target' => $p['spec_target'],
                    'alert_on'    => $p['alert_on'],
                    'alert_level' => $p['alert_level'],
                    'sort_order'  => $i,
                    'is_active'   => true,
                ]);
                $createdParams2[] = $param;
            }
        } else {
            $createdParams2 = $t2->parameters()->get()->all();
        }

        $slots2 = ['Shift 1', 'Shift 2', 'Shift 3'];
        $createdSlots2 = [];
        if ($t2->timeSlots()->count() === 0) {
            foreach ($slots2 as $i => $label) {
                $createdSlots2[] = $t2->timeSlots()->create(['label' => $label, 'sort_order' => $i]);
            }
        } else {
            $createdSlots2 = $t2->timeSlots()->get()->all();
        }

        // Create 30 days of records for template 1
        if ($t1->records()->count() === 0 && !empty($createdParams1) && !empty($createdSlots1)) {
            for ($d = 29; $d >= 0; $d--) {
                $date = now()->subDays($d)->toDateString();
                foreach ($createdSlots1 as $slot) {
                    $record = ChecksheetRecord::create([
                        'template_id'  => $t1->id,
                        'factory_id'   => $factory->id,
                        'record_date'  => $date,
                        'time_slot_id' => $slot->id,
                        'status'       => 'submitted',
                        'submitted_by' => $user->id,
                        'submitted_at' => now()->subDays($d),
                    ]);

                    foreach ($createdParams1 as $param) {
                        $value = null;
                        $isAlert = false;
                        $alertLvl = null;
                        if ($param->type === 'number') {
                            $base = match(true) {
                                str_contains($param->name, 'Motor')   => 65,
                                str_contains($param->name, 'Ambient') => 30,
                                str_contains($param->name, 'แรงดัน') => 220,
                                default => 50,
                            };
                            // Occasionally generate out-of-spec value
                            $offset = rand(-15, 20);
                            $value = $base + $offset;
                            $alertLvl = $param->checkValue($value);
                            $isAlert = $alertLvl !== null;
                        } elseif ($param->type === 'pass_fail') {
                            $value = rand(0, 10) > 1 ? 'Pass' : 'Fail';
                            $isAlert = $value === 'Fail';
                        } elseif ($param->type === 'text') {
                            $value = $d % 5 === 0 ? 'ปกติ' : null;
                        }

                        ChecksheetRecordValue::create([
                            'record_id'    => $record->id,
                            'parameter_id' => $param->id,
                            'value'        => $value !== null ? (string)$value : null,
                            'is_alert'     => $isAlert,
                            'alert_level'  => $isAlert ? $alertLvl : null,
                            'recorded_by'  => $user->id,
                        ]);
                    }
                }
            }
        }

        // Create 30 days of records for template 2
        if ($t2->records()->count() === 0 && !empty($createdParams2) && !empty($createdSlots2)) {
            for ($d = 29; $d >= 0; $d--) {
                $date = now()->subDays($d)->toDateString();
                foreach ($createdSlots2 as $slot) {
                    $record = ChecksheetRecord::create([
                        'template_id'  => $t2->id,
                        'factory_id'   => $factory->id,
                        'record_date'  => $date,
                        'time_slot_id' => $slot->id,
                        'status'       => 'submitted',
                        'submitted_by' => $user->id,
                        'submitted_at' => now()->subDays($d),
                    ]);

                    foreach ($createdParams2 as $param) {
                        $value = null;
                        $isAlert = false;
                        $alertLvl = null;
                        if ($param->type === 'number') {
                            $value = match(true) {
                                str_contains($param->name, 'Dimension') => round(10.0 + (rand(-10, 10) / 100), 3),
                                str_contains($param->name, 'Weight')    => 100 + rand(-2, 2),
                                str_contains($param->name, 'จำนวนผลิต') => rand(480, 600),
                                str_contains($param->name, 'NG')        => rand(0, 5),
                                default => rand(0, 100),
                            };
                            $alertLvl = $param->checkValue($value);
                            $isAlert = $alertLvl !== null;
                        } elseif ($param->type === 'pass_fail') {
                            $value   = rand(0, 10) > 1 ? 'Pass' : 'Fail';
                            $isAlert = $value === 'Fail';
                        }
                        ChecksheetRecordValue::create([
                            'record_id'    => $record->id,
                            'parameter_id' => $param->id,
                            'value'        => $value !== null ? (string)$value : null,
                            'is_alert'     => $isAlert,
                            'alert_level'  => $isAlert ? $alertLvl : null,
                            'recorded_by'  => $user->id,
                        ]);
                    }
                }
            }
        }

        // Create dashboard
        $dashboard = Dashboard::firstOrCreate(
            ['slug' => 'line-a-overview'],
            [
                'name'          => 'Line A Overview',
                'factory_scope' => 'own_factory',
                'is_public'     => true,
                'created_by'    => $user->id,
            ]
        );

        if ($dashboard->widgets()->count() === 0) {
            $motorParam = $t1->parameters()->where('name', 'like', '%Motor%')->first();

            $dashboard->widgets()->create([
                'widget_type' => 'kpi_card',
                'title'       => 'อุณหภูมิ Motor ล่าสุด',
                'config'      => ['template_id' => $t1->id, 'parameter_ids' => $motorParam ? [$motorParam->id] : [], 'date_range' => 'last_30_days'],
                'pos_x' => 0, 'pos_y' => 0, 'width' => 3, 'height' => 2,
            ]);
            $dashboard->widgets()->create([
                'widget_type' => 'line_chart',
                'title'       => 'อุณหภูมิ 30 วัน',
                'config'      => ['template_id' => $t1->id, 'parameter_ids' => $motorParam ? [$motorParam->id] : [], 'date_range' => 'last_30_days'],
                'pos_x' => 3, 'pos_y' => 0, 'width' => 9, 'height' => 4,
            ]);
            $dashboard->widgets()->create([
                'widget_type' => 'heatmap',
                'title'       => 'Alert Heatmap 7 วัน',
                'config'      => ['template_id' => $t1->id, 'parameter_ids' => [], 'date_range' => 'last_7_days'],
                'pos_x' => 0, 'pos_y' => 4, 'width' => 6, 'height' => 4,
            ]);
            $dashboard->widgets()->create([
                'widget_type' => 'data_table',
                'title'       => 'บันทึกล่าสุด',
                'config'      => ['template_id' => $t1->id, 'parameter_ids' => [], 'date_range' => 'last_7_days'],
                'pos_x' => 6, 'pos_y' => 4, 'width' => 6, 'height' => 4,
            ]);
        }

        $this->command->info('ChecksheetSeeder completed');
    }
}
