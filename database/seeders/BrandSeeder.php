<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Siemens',     'slug' => 'siemens',     'country' => 'DE'],
            ['name' => 'Bronkhorst',  'slug' => 'bronkhorst',  'country' => 'NL'],
            ['name' => 'Honeywell',   'slug' => 'honeywell',   'country' => 'US'],
            ['name' => 'Endress+Hauser', 'slug' => 'endress-hauser', 'country' => 'CH'],
            ['name' => 'ABB',         'slug' => 'abb',         'country' => 'CH'],
            ['name' => 'Schneider Electric', 'slug' => 'schneider-electric', 'country' => 'FR'],
            ['name' => 'Yokogawa',    'slug' => 'yokogawa',    'country' => 'JP'],
            ['name' => 'Emerson',     'slug' => 'emerson',     'country' => 'US'],
        ];

        foreach ($brands as $i => $b) {
            Brand::updateOrCreate(
                ['slug' => $b['slug']],
                $b + ['sort_order' => $i + 1, 'is_active' => true, 'is_featured' => $i < 4]
            );
        }
    }
}
