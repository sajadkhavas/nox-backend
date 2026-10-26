<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $settings = [
            ['group' => 'brand', 'key' => 'brand.identity', 'type' => 'json', 'label' => 'هویت برند', 'value' => [
                'name' => 'LBB',
                'nameFa' => 'ال‌بی‌بی',
                'category' => 'پوشاک خیابانی و استریت‌ویر',
                'city' => 'کرج',
                'province' => 'البرز',
                'physicalLocation' => 'پاساژ مهستان',
                'physicalLocationShort' => 'کرج، پاساژ مهستان',
                'instagramHandle' => '@lbbclo',
                'instagramUrl' => 'https://www.instagram.com/lbbclo',
                'slogan' => 'الهام‌گرفته از ذهنی خلاق',
                'storyTitle' => 'از رگال تا فروشگاه',
                'descriptor' => 'پوشاک خیابانی، استریت‌ویر و آیتم‌های وارداتی منتخب LBB در کرج؛ با راهنمای سایز و جزئیات روشن محصول.',
                'shortIntroduction' => 'LBB فروشگاه پوشاک خیابانی و استریت‌ویر در کرج است؛ با مجموعه‌ای منتخب از تولیدات و آیتم‌های وارداتی.',
            ]],
            ['group' => 'brand', 'key' => 'brand.copy', 'type' => 'json', 'label' => 'متن‌های اصلی برند و صفحه خانه', 'value' => [
                'homepageTitle' => 'LBB | پوشاک خیابانی و استریت‌ویر در کرج',
                'homepageDescription' => 'خرید پوشاک خیابانی و استریت‌ویر LBB در کرج؛ تیشرت، شلوار، کتونی و آیتم‌های وارداتی منتخب با راهنمای سایز و جزئیات محصول.',
                'heroEyebrow' => 'LBB / STREETWEAR / KARAJ',
                'heroTitle' => 'از پینترست تا رگال LBB',
                'heroBody' => 'آیتم‌های استریت‌ویر منتخب، از فرم‌های ترند و الهام‌های روز تا پوشاک وارداتی؛ با راهنمای سایز و جزئیات هر محصول برای انتخاب دقیق‌تر.',
                'primaryCta' => 'خرید جدیدترین‌ها',
                'secondaryCta' => 'اطلاعات فروشگاه حضوری',
                'storeLocationLabel' => 'فروشگاه حضوری ال‌بی‌بی — کرج، پاساژ مهستان',
            ]],
            ['group' => 'contact', 'key' => 'contact.public', 'type' => 'json', 'label' => 'اطلاعات تماس عمومی', 'value' => [
                'phone' => '026-3256-0477',
                'whatsapp' => '0902-858-4879',
                'instagramHandle' => '@lbbclo',
                'instagramUrl' => 'https://www.instagram.com/lbbclo',
                'locationLabel' => 'کرج، پاساژ مهستان',
                'city' => 'کرج',
                'province' => 'البرز',
            ]],
            ['group' => 'announcement', 'key' => 'announcement.messages', 'type' => 'json', 'label' => 'پیام‌های نوار بالای سایت', 'value' => [
                ['text' => 'LBB؛ الهام‌گرفته از ذهنی خلاق', 'href' => '/shop'],
                ['text' => 'فروشگاه حضوری LBB — کرج، پاساژ مهستان', 'href' => '/contact'],
                ['text' => 'راهنمای سایز اختصاصی برای انتخاب دقیق‌تر', 'href' => '/size-guide'],
            ]],
            ['group' => 'navigation', 'key' => 'navigation.shop', 'type' => 'json', 'label' => 'منوی فروشگاه', 'value' => [
                ['label' => 'همه محصولات', 'latin' => 'SHOP ALL', 'description' => 'مشاهده کامل کاتالوگ', 'href' => '/shop'],
                ['label' => 'تیشرت', 'latin' => 'T-SHIRTS', 'description' => 'اورسایز، باکس، یقه‌دار، آستین‌بلند و حلقه‌ای', 'href' => '/tshirts'],
                ['label' => 'سویشرت / هودی', 'latin' => 'SWEATSHIRTS', 'description' => 'در موجودی فعلی، هودی فعال است', 'href' => '/hoodies'],
                ['label' => 'شلوار', 'latin' => 'PANTS', 'description' => 'جین، پارچه‌ای، اسلش، جورتز و شرت', 'href' => '/pants'],
                ['label' => 'کتونی', 'latin' => 'SNEAKERS', 'description' => 'دسته فیلترمحور بر اساس برند، سایز، رنگ و استایل', 'href' => '/shoes'],
                ['label' => 'جوراب', 'latin' => 'SOCKS', 'description' => 'موجودی فعلی کاتالوگ', 'href' => '/socks'],
            ]],
            ['group' => 'navigation', 'key' => 'navigation.editorial', 'type' => 'json', 'label' => 'منوی محتوایی', 'value' => [
                ['label' => 'کالکشن‌ها', 'latin' => 'COLLECTIONS', 'description' => 'دراپ‌ها و فصل‌های LBB', 'href' => '/collections'],
                ['label' => 'لوک‌بوک', 'latin' => 'LOOKBOOK', 'description' => 'استایل‌ها در بافت شهری', 'href' => '/lookbook'],
                ['label' => 'ژورنال', 'latin' => 'JOURNAL', 'description' => 'راهنما، فرهنگ و متریال', 'href' => '/journal'],
            ]],
            ['group' => 'navigation', 'key' => 'navigation.service', 'type' => 'json', 'label' => 'منوی خدمات', 'value' => [
                ['label' => 'راهنمای سایز', 'latin' => 'SIZE GUIDE', 'href' => '/size-guide'],
                ['label' => 'ارسال و مرجوعی', 'latin' => 'SHIPPING', 'href' => '/shipping-returns'],
                ['label' => 'پیگیری سفارش', 'latin' => 'TRACK ORDER', 'href' => '/track-order'],
                ['label' => 'سوالات متداول', 'latin' => 'FAQ', 'href' => '/faq'],
                ['label' => 'تماس', 'latin' => 'CONTACT', 'href' => '/contact'],
            ]],
            ['group' => 'navigation', 'key' => 'navigation.brand', 'type' => 'json', 'label' => 'منوی برند', 'value' => [
                ['label' => 'درباره LBB', 'latin' => 'ABOUT', 'href' => '/about'],
                ['label' => 'قوانین', 'latin' => 'TERMS', 'href' => '/terms'],
                ['label' => 'حریم خصوصی', 'latin' => 'PRIVACY', 'href' => '/privacy'],
            ]],
            ['group' => 'home', 'key' => 'home.presentation', 'type' => 'json', 'label' => 'چیدمان و انتخاب‌های صفحه خانه', 'value' => [
                'heroProductSlug' => 'lbb-signature-tee',
                'categoryOrder' => ['tshirts', 'hoodies', 'pants', 'shoes', 'socks'],
                'sections' => [
                    'ticker',
                    'trust',
                    'categories',
                    'products',
                    'drop_story',
                    'decision_support',
                    'local_store',
                    'instagram',
                ],
            ]],
            ['group' => 'home', 'key' => 'home.brand_intro', 'type' => 'json', 'label' => 'معرفی اولین ورود', 'value' => [
                'enabled' => true,
                'version' => 'v1',
                'eyebrow' => 'LBB / STREETWEAR',
                'title' => 'از رگال تا فروشگاه',
                'body' => 'LBB؛ الهام‌گرفته از ذهنی خلاق',
                'storyCta' => 'داستان LBB',
                'storeCta' => 'ورود به فروشگاه',
            ]],
            ['group' => 'seo', 'key' => 'seo.defaults', 'type' => 'json', 'label' => 'تنظیمات پیش‌فرض SEO', 'value' => [
                'siteName' => 'LBB',
                'locale' => 'fa_IR',
                'organizationDescription' => 'برند پوشاک خیابانی و استریت‌ویر LBB',
                'instagramUrl' => 'https://www.instagram.com/lbbclo',
            ]],
        ];

        foreach ($settings as $setting) {
            DB::table('store_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'value' => json_encode($setting['value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'label' => $setting['label'],
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        $pages = [
            [
                'slug' => 'about',
                'type' => 'page',
                'title' => 'درباره LBB',
                'excerpt' => 'LBB؛ پوشاک خیابانی و استریت‌ویر منتخب در کرج، از رگال تا فروشگاه حضوری و آنلاین.',
                'content' => '<p>LBB با تمرکز بر پوشاک خیابانی، استریت‌ویر و آیتم‌های وارداتی منتخب شکل گرفته است.</p><p>هدف ما این است که پیش از انتخاب، اطلاعات محصول، تن‌خور، سایز و جزئیات لازم روشن باشد.</p><p>فروشگاه حضوری LBB در کرج، پاساژ مهستان در کنار فروشگاه آنلاین در دسترس است.</p>',
                'meta_title' => 'درباره LBB | از رگال تا فروشگاه',
                'meta_description' => 'داستان LBB؛ فروشگاه پوشاک خیابانی و استریت‌ویر در کرج با مجموعه‌ای منتخب از تولیدات و آیتم‌های وارداتی.',
            ],
            [
                'slug' => 'contact',
                'type' => 'page',
                'title' => 'تماس با LBB',
                'excerpt' => 'راه‌های تماس عمومی و اطلاعات فروشگاه حضوری LBB در کرج.',
                'content' => '<p>فروشگاه حضوری LBB: کرج، پاساژ مهستان.</p><p>تلفن: 026-3256-0477</p><p>واتساپ: 0902-858-4879</p><p>اینستاگرام: @lbbclo</p>',
                'meta_title' => 'تماس با LBB | فروشگاه حضوری کرج',
                'meta_description' => 'راه‌های تماس با LBB و اطلاعات فروشگاه حضوری در کرج، پاساژ مهستان.',
            ],
        ];

        foreach ($pages as $page) {
            DB::table('content_pages')->insertOrIgnore([
                'public_id' => (string) Str::ulid(),
                'type' => $page['type'],
                'slug' => $page['slug'],
                'title' => $page['title'],
                'excerpt' => $page['excerpt'],
                'content' => $page['content'],
                'meta_title' => $page['meta_title'],
                'meta_description' => $page['meta_description'],
                'status' => 'published',
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('content_pages')->where('slug', $page['slug'])->update([
                'type' => $page['type'],
                'title' => $page['title'],
                'excerpt' => $page['excerpt'],
                'content' => $page['content'],
                'meta_title' => $page['meta_title'],
                'meta_description' => $page['meta_description'],
                'status' => 'published',
                'published_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('content_pages')->whereIn('slug', ['about', 'contact'])->delete();

        DB::table('store_settings')->whereIn('key', [
            'brand.identity',
            'brand.copy',
            'contact.public',
            'announcement.messages',
            'navigation.shop',
            'navigation.editorial',
            'navigation.service',
            'navigation.brand',
            'home.presentation',
            'home.brand_intro',
            'seo.defaults',
        ])->delete();
    }
};
