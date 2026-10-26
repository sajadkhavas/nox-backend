<?php

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['title' => 'تجهیزات ابزار دقیق صنعتی', 'subtitle' => 'نمایندگی رسمی برندهای اروپایی', 'image' => 'sliders/slide-1.jpg', 'link' => '/products', 'button_text' => 'مشاهده محصولات', 'sort_order' => 1],
            ['title' => 'استعلام قیمت آنلاین',       'subtitle' => 'پاسخ ظرف ۲۴ ساعت کاری',       'image' => 'sliders/slide-2.jpg', 'link' => '/contact',  'button_text' => 'ثبت استعلام',    'sort_order' => 2],
        ];

        foreach ($items as $i) {
            Slider::updateOrCreate(['title' => $i['title']], $i + ['is_active' => true]);
        }
    }
}
