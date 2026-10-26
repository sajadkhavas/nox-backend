<?php
namespace Database\Seeders;

use App\Models\SeoMeta;
use Illuminate\Database\Seeder;

class SeoMetaSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'page_key'         => 'home',
                'page_label'       => 'صفحه اصلی',
                'meta_title'       => 'پارس ابزار دقیق | تجهیزات صنعتی و ابزار دقیق',
                'meta_description' => 'فروش و عرضه انواع ابزار دقیق، تجهیزات صنعتی و اندازه‌گیری با بهترین کیفیت و قیمت مناسب',
                'meta_keywords'    => 'ابزار دقیق، تجهیزات صنعتی، اندازه‌گیری، پارس ابزار',
                'og_title'         => 'پارس ابزار دقیق',
                'og_description'   => 'فروش انواع ابزار دقیق و تجهیزات صنعتی',
                'robots'           => 'index,follow',
                'is_active'        => true,
            ],
            [
                'page_key'         => 'products',
                'page_label'       => 'محصولات',
                'meta_title'       => 'محصولات | پارس ابزار دقیق',
                'meta_description' => 'مشاهده تمام محصولات ابزار دقیق و تجهیزات صنعتی پارس ابزار',
                'meta_keywords'    => 'محصولات، ابزار دقیق، تجهیزات صنعتی',
                'robots'           => 'index,follow',
                'is_active'        => true,
            ],
            [
                'page_key'         => 'blog',
                'page_label'       => 'بلاگ',
                'meta_title'       => 'بلاگ | پارس ابزار دقیق',
                'meta_description' => 'مقالات و اخبار حوزه ابزار دقیق و تجهیزات صنعتی',
                'meta_keywords'    => 'بلاگ، مقالات، ابزار دقیق',
                'robots'           => 'index,follow',
                'is_active'        => true,
            ],
            [
                'page_key'         => 'contact',
                'page_label'       => 'تماس با ما',
                'meta_title'       => 'تماس با ما | پارس ابزار دقیق',
                'meta_description' => 'راه‌های ارتباطی با پارس ابزار دقیق',
                'robots'           => 'index,follow',
                'is_active'        => true,
            ],
            [
                'page_key'         => 'about',
                'page_label'       => 'درباره ما',
                'meta_title'       => 'درباره ما | پارس ابزار دقیق',
                'meta_description' => 'آشنایی با شرکت پارس ابزار دقیق و سابقه فعالیت ما',
                'robots'           => 'index,follow',
                'is_active'        => true,
            ],
        ];

        foreach ($pages as $page) {
            SeoMeta::firstOrCreate(
                ['page_key' => $page['page_key']],
                $page
            );
        }

        $this->command->info('✅ SeoMeta seeder completed — ' . count($pages) . ' records');
    }
}
