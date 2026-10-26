<?php

namespace Database\Seeders;

use App\Models\ContentPage;
use Illuminate\Database\Seeder;
use RuntimeException;

class LBBStagingSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('LBB staging data must never be seeded in production.');
        }

        foreach ([
            'about' => 'درباره LBB',
            'quality' => 'رویکرد کیفیت',
            'shipping' => 'شرایط ارسال',
            'privacy' => 'حریم خصوصی',
            'terms' => 'شرایط استفاده',
        ] as $slug => $title) {
            ContentPage::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'type' => 'page',
                    'title' => $title,
                    'excerpt' => 'محتوای آزمایشی برای بررسی قرارداد مدیریت محتوا.',
                    'content' => 'این صفحه فقط داده آزمایشی محیط توسعه است و هیچ نشانی، شماره تماس، مبلغ ارسال یا سیاست واقعی فروشگاه را اعلام نمی‌کند.',
                    'status' => 'published',
                    'published_at' => now(),
                ],
            );
        }
    }
}
