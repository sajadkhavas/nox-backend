<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'ژنراتورهای گاز', 'name_en' => 'gas-generators', 'slug' => 'gas-generators', 'icon' => 'Zap', 'sort_order' => 1,
                'subcategories' => [
                    ['name' => 'ژنراتور هیدروژن', 'slug' => 'hydrogen-gen', 'full_name_en' => 'Hydrogen Generator'],
                    ['name' => 'ژنراتور نیتروژن', 'slug' => 'nitrogen-gen', 'full_name_en' => 'Nitrogen Generator'],
                    ['name' => 'ژنراتور هوای خشک', 'slug' => 'dry-air-gen', 'full_name_en' => 'Dry Air Generator'],
                ]],
            ['name' => 'پمپ‌های آزمایشگاهی', 'name_en' => 'lab-pumps', 'slug' => 'lab-pumps', 'icon' => 'Droplets', 'sort_order' => 2,
                'subcategories' => [
                    ['name' => 'پمپ خلاء روتاری', 'slug' => 'vacuum-pump', 'full_name_en' => 'Rotary Vacuum Pump'],
                    ['name' => 'پمپ پریستالتیک', 'slug' => 'peristaltic-pump', 'full_name_en' => 'Peristaltic Pump'],
                    ['name' => 'پمپ دیافراگمی', 'slug' => 'diaphragm-pump', 'full_name_en' => 'Diaphragm Pump'],
                ]],
            ['name' => 'دتکتورهای گاز', 'name_en' => 'gas-detectors', 'slug' => 'gas-detectors', 'icon' => 'AlertTriangle', 'sort_order' => 3,
                'subcategories' => [
                    ['name' => 'دتکتور گاز سمی', 'slug' => 'toxic-detector', 'full_name_en' => 'Toxic Gas Detector'],
                    ['name' => 'دتکتور گاز قابل اشتعال', 'slug' => 'flammable-detector', 'full_name_en' => 'Flammable Gas Detector'],
                    ['name' => 'دتکتور چند گازی', 'slug' => 'multi-gas', 'full_name_en' => 'Multi-Gas Detector'],
                ]],
            ['name' => 'فلومتر و فلوکنترلر', 'name_en' => 'flow-meters', 'slug' => 'flow-meters', 'icon' => 'Gauge', 'sort_order' => 4,
                'subcategories' => [
                    ['name' => 'فلومتر الکترومغناطیسی', 'slug' => 'electromagnetic-flow', 'full_name_en' => 'Electromagnetic Flow Meter'],
                    ['name' => 'فلوکنترلر جرمی', 'slug' => 'mass-flow-controller', 'full_name_en' => 'Mass Flow Controller'],
                    ['name' => 'فلومتر اولتراسونیک', 'slug' => 'ultrasonic-flow', 'full_name_en' => 'Ultrasonic Flow Meter'],
                ]],
            ['name' => 'تجهیزات PLC', 'name_en' => 'plc-equipment', 'slug' => 'plc-equipment', 'icon' => 'Cpu', 'sort_order' => 5,
                'subcategories' => [
                    ['name' => 'ماژول CPU', 'slug' => 'plc-cpu', 'full_name_en' => 'PLC CPU Module'],
                    ['name' => 'ماژول ورودی/خروجی', 'slug' => 'plc-io', 'full_name_en' => 'PLC I/O Module'],
                    ['name' => 'پنل HMI', 'slug' => 'hmi-panel', 'full_name_en' => 'HMI Touch Panel'],
                ]],
            ['name' => 'کالیبراسیون و لوازم جانبی', 'name_en' => 'calibration', 'slug' => 'calibration', 'icon' => 'Settings', 'sort_order' => 6, 'subcategories' => []],
        ];

        foreach ($categories as $data) {
            $subs = $data['subcategories']; unset($data['subcategories']);
            $cat  = Category::updateOrCreate(['slug' => $data['slug']], $data);
            foreach ($subs as $i => $sub) {
                $cat->subcategories()->updateOrCreate(['slug' => $sub['slug']], $sub + ['sort_order' => $i + 1]);
            }
        }
    }
}
