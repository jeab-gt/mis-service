<?php

namespace Database\Seeders;

use App\Models\OptionSet;
use Illuminate\Database\Seeder;

class OptionSetSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Factories — dynamic from masters table
        OptionSet::firstOrCreate(['code' => 'factories'], [
            'name_th'           => 'โรงงาน',
            'name_en'           => 'Factories',
            'source_type'       => 'master',
            'master_type'       => 'factory',
            'filter_by_factory' => false,
            'description'       => 'List of all factories from master data',
        ]);

        // 2. Departments — filtered by factory
        OptionSet::firstOrCreate(['code' => 'departments'], [
            'name_th'           => 'แผนก',
            'name_en'           => 'Departments',
            'source_type'       => 'master',
            'master_type'       => 'department',
            'filter_by_factory' => true,
            'description'       => 'Departments filtered by factory',
        ]);

        // 3. Sections — filtered by factory
        OptionSet::firstOrCreate(['code' => 'sections'], [
            'name_th'           => 'ส่วนงาน',
            'name_en'           => 'Sections',
            'source_type'       => 'master',
            'master_type'       => 'section',
            'filter_by_factory' => true,
            'description'       => 'Sections filtered by factory',
        ]);

        // 4. Users in factory — dynamic from users table
        OptionSet::firstOrCreate(['code' => 'users_in_factory'], [
            'name_th'           => 'ผู้ใช้ในโรงงาน',
            'name_en'           => 'Users in Factory',
            'source_type'       => 'users',
            'filter_by_factory' => true,
            'description'       => 'Active users filtered by factory',
        ]);

        // 5. Device types — static items
        $deviceTypes = OptionSet::firstOrCreate(['code' => 'device_types'], [
            'name_th'     => 'ประเภทอุปกรณ์',
            'name_en'     => 'Device Types',
            'source_type' => 'static',
            'description' => 'IT device types',
        ]);
        if ($deviceTypes->items()->count() === 0) {
            $items = [
                ['value' => 'computer',  'label_th' => 'คอมพิวเตอร์',      'label_en' => 'Computer'],
                ['value' => 'laptop',    'label_th' => 'โน้ตบุ๊ก',          'label_en' => 'Laptop'],
                ['value' => 'printer',   'label_th' => 'เครื่องพิมพ์',      'label_en' => 'Printer'],
                ['value' => 'scanner',   'label_th' => 'เครื่องสแกน',       'label_en' => 'Scanner'],
                ['value' => 'network',   'label_th' => 'อุปกรณ์เครือข่าย', 'label_en' => 'Network Device'],
                ['value' => 'phone',     'label_th' => 'โทรศัพท์',          'label_en' => 'Phone'],
                ['value' => 'other',     'label_th' => 'อื่นๆ',             'label_en' => 'Other'],
            ];
            foreach ($items as $i => $item) {
                $deviceTypes->items()->create([...$item, 'sort_order' => $i, 'is_active' => true]);
            }
        }

        // 6. Priority levels — static items
        $priority = OptionSet::firstOrCreate(['code' => 'priority_levels'], [
            'name_th'     => 'ระดับความเร่งด่วน',
            'name_en'     => 'Priority Levels',
            'source_type' => 'static',
            'description' => 'Request priority levels',
        ]);
        if ($priority->items()->count() === 0) {
            $items = [
                ['value' => 'critical', 'label_th' => 'วิกฤต',     'label_en' => 'Critical'],
                ['value' => 'high',     'label_th' => 'สูง',        'label_en' => 'High'],
                ['value' => 'medium',   'label_th' => 'กลาง',       'label_en' => 'Medium'],
                ['value' => 'low',      'label_th' => 'ต่ำ',        'label_en' => 'Low'],
            ];
            foreach ($items as $i => $item) {
                $priority->items()->create([...$item, 'sort_order' => $i, 'is_active' => true]);
            }
        }
    }
}
