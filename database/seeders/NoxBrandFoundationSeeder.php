<?php

namespace Database\Seeders;

use App\Models\ContentPage;
use App\Models\StoreSetting;
use App\Support\StorefrontPresentationDefaults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Explicit NOX-local onboarding. Not registered in DatabaseSeeder or migrations.
 * This changes public brand content only; never products, customers, orders, or payment switches.
 */
class NoxBrandFoundationSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'nox') {
            throw new RuntimeException('Run this onboarding only on the local NOX database named nox.');
        }

        $backupDirectory = storage_path('app/private/nox-onboarding');
        if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0700, true)) {
            throw new RuntimeException('Cannot create NOX settings backup directory.');
        }
        $backupPath = $backupDirectory.'/'.now()->format('Ymd-His').'-'.Str::random(8).'.json';
        $snapshot = [
            'store_settings' => StoreSetting::query()->get()->toArray(),
            'content_pages' => ContentPage::query()->get()->toArray(),
        ];
        if (file_put_contents($backupPath, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Cannot save settings backup; no changes applied.');
        }

        DB::transaction(function (): void {
            $settings = [];
            foreach (StoreSetting::query()->public()->get() as $row) {
                $settings[$row->group][$row->key] = $row->typedValue();
            }
            $settings = StorefrontPresentationDefaults::merge($settings);
            $settings = $this->localize($settings);

            $address = 'تهران، چهارراه استانبول، پاساژ فردوسی، طبقه ۲، پلاک ۳۵۷';
            $hours = ['شنبه تا چهارشنبه: ۱۱ تا ۲۰', 'پنجشنبه: ۱۱ تا ۱۵', 'جمعه: تعطیل'];
            $story = 'NOX از سال ۲۰۲۱ با الهام از استایل شخصی استریت، لش و بگ شکل گرفت. محصولات به سلیقه مجموعه توسط تولیدکننده طرف قرارداد تولید می‌شوند. پوشاک NOX یونیسکس است و محدودیت سنی ندارد.';

            $settings['brand']['brand.identity'] = [
                'name' => 'NOX Collection', 'nameFa' => 'نوکس',
                'category' => 'پوشاک استریت‌ور یونیسکس',
                'city' => 'تهران', 'province' => 'تهران',
                'physicalLocation' => 'پاساژ فردوسی',
                'physicalLocationShort' => 'تهران، چهارراه استانبول، پاساژ فردوسی',
                'instagramHandle' => '@collection_nox',
                'instagramUrl' => 'https://www.instagram.com/collection_nox',
                'slogan' => 'NOX ON TOP', 'storyTitle' => 'از استایل شخصی تا NOX',
                'descriptor' => 'پوشاک استریت‌ور یونیسکس؛ فرم‌های آزاد، لش و بگ از NOX Collection در تهران.',
                'shortIntroduction' => $story,
            ];
            $settings['brand']['brand.copy'] = [
                'homepageTitle' => 'NOX Collection | پوشاک استریت‌ور یونیسکس',
                'homepageDescription' => 'پوشاک استریت‌ور NOX؛ تیشرت باکسی، هودی و دورس، شلوار بگ و جین، وست و کت. فروش تکی، ارسال با تیپاکس به سراسر ایران و خرید حضوری در تهران.',
                'heroEyebrow' => 'NOX / STREETWEAR / EST. 2021',
                'heroTitle' => 'استایل خودت را بساز',
                'heroBody' => 'فرم‌های آزاد، لش و بگ؛ برای سلیقه تو، فراتر از سن و جنسیت.',
                'primaryCta' => 'مشاهده تازه‌ها', 'secondaryCta' => 'فروشگاه حضوری',
                'storeLocationLabel' => $address,
            ];
            $settings['contact']['contact.public'] = [
                'phone' => '09309599920', 'secondaryPhone' => '09101872731',
                'whatsapp' => '', 'email' => null,
                'instagramHandle' => '@collection_nox',
                'instagramUrl' => 'https://www.instagram.com/collection_nox',
                'telegramChannel' => 'https://t.me/noxcolection',
                'telegramContact' => 'https://t.me/nox_collection',
                'locationLabel' => $address, 'addressLine' => $address,
                'mapUrl' => null, 'openingHours' => $hours,
                'city' => 'تهران', 'province' => 'تهران',
            ];
            $settings['announcement']['announcement.messages'] = [
                ['text' => 'NOX ON TOP', 'href' => '/shop', 'enabled' => true],
                ['text' => 'ارسال به سراسر ایران با تیپاکس — هزینه با مشتری', 'href' => '/shipping-returns', 'enabled' => true],
                ['text' => 'فروش تکی — از یک عدد', 'href' => '/shop', 'enabled' => true],
                ['text' => 'NOX COLLECTION / EST. 2021', 'href' => '/about', 'enabled' => true],
                ['text' => $address, 'href' => '/contact', 'enabled' => true],
            ];
            // Keep section structure; only remove inherited LBB products and store photographs.
            $settings['home']['home.presentation']['heroProductSlug'] = '';
            $settings['home']['home.presentation']['heroImagePath'] = null;
            $settings['home']['home.presentation']['heroImageUrl'] = null;
            $settings['home']['home.presentation']['heroImageAlt'] = '';
            $settings['home']['home.presentation']['productCuration'] = [
                'mode' => 'newest', 'count' => 4, 'manualProductSlugs' => [],
            ];
            $settings['home']['home.brand_intro'] = array_replace($settings['home']['home.brand_intro'], [
                'enabled' => false, 'version' => 'nox-v1', 'eyebrow' => 'NOX / EST. 2021',
                'title' => 'از استایل شخصی تا NOX', 'body' => $story, 'storyCta' => 'داستان NOX',
            ]);
            $settings['home']['home.ticker'] = ['NOX ON TOP', 'پوشاک استریت‌ور', 'یونیسکس', 'فرم‌های آزاد', 'EST. 2021'];
            $settings['home']['home.section_copy']['products'] = array_replace($settings['home']['home.section_copy']['products'], [
                'label' => 'تازه‌های NOX', 'title' => 'تازه رسیده‌ها',
                'lede' => 'جدیدترین محصولات منتشرشده؛ جزئیات، رنگ و سایز هر مدل را در صفحه محصول ببین.',
            ]);
            $settings['home']['home.local_store'] = array_replace($settings['home']['home.local_store'], [
                'eyebrow' => 'فروشگاه حضوری NOX', 'title' => 'از نزدیک انتخاب کن.',
                'body' => $address, 'imagePath' => null, 'imageUrl' => null,
                'instagramHref' => 'https://www.instagram.com/collection_nox',
            ]);
            $settings['home']['home.trust'] = [
                ['icon' => 'shipping', 'title' => 'ارسال با تیپاکس', 'description' => 'ارسال سراسر ایران؛ هزینه با مشتری', 'href' => '/shipping-returns', 'enabled' => true],
                ['icon' => 'location', 'title' => 'فروشگاه حضوری تهران', 'description' => $address, 'href' => '/contact', 'enabled' => true],
                ['icon' => 'support', 'title' => 'راهنمای انتخاب', 'description' => 'پیش از خرید درباره اندازه و تن‌خور بپرس.', 'href' => '/contact', 'enabled' => true],
            ];
            $settings['home']['home.decision_support']['checks'] = [
                'رنگ و سایز موجود هر محصول را پیش از خرید بررسی کن',
                'برای انتخاب سایز، مشخصات همان مدل را ببین یا از مجموعه راهنمایی بگیر',
                'روش شست‌وشو و نگهداری را برای همان محصول بررسی کن',
                'برای خرید حضوری، آدرس و ساعت کاری را در صفحه تماس ببین',
            ];
            $settings['policy']['policy.returns'] = [
                'enabled' => true, 'verification' => 'pending', 'exchangeEnabled' => true,
                'returnWindowDays' => null, 'refundTimeLabel' => null,
                'customerPaysReturnShipping' => null, 'quickIssueNoticeHours' => null,
            ];
            $settings['seo']['seo.defaults'] = [
                'siteName' => 'NOX Collection', 'locale' => 'fa_IR',
                'organizationDescription' => $story,
                'instagramUrl' => 'https://www.instagram.com/collection_nox',
            ];
            $settings['brand']['brand.business_profile'] = [
                'establishedYear' => 2021, 'collectionName' => 'Noxi',
                'audience' => 'یونیسکس، بدون محدودیت سنی',
                'production' => 'تولید توسط شرکت طرف قرارداد به سلیقه مجموعه',
                'salesMode' => 'retail', 'minimumQuantity' => 1,
                'delivery' => 'تیپاکس سراسر ایران، هزینه با مشتری؛ خرید حضوری نیز امکان دارد',
                'tracking' => 'بارکد یا پیام تیپاکس پس از ثبت مرسوله',
                'paymentStatus' => 'درگاه فعال هنوز معرفی نشده؛ این تنظیم درگاه یا کارت‌به‌کارت را فعال نمی‌کند',
                'exchange' => 'تعویض سایز و مدل پذیرفته می‌شود؛ مهلت و جزئیات هنوز نهایی نیست',
                'care' => 'اطلاعات جنس، اندازه، وزن و شست‌وشو باید برای هر محصول تأیید و ثبت شوند',
                'designAttribution' => 'طرح‌های الهام‌گرفته؛ بدون ادعای اصالت برند خارجی',
            ];

            foreach ($settings as $group => $items) {
                foreach ($items as $key => $value) {
                    $attributes = ['group' => $group, 'type' => 'json', 'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), 'is_public' => true];
                    $existing = StoreSetting::query()->where('key', $key)->first();
                    if ($existing) {
                        $existing->update($attributes);
                    } else {
                        StoreSetting::query()->create(['key' => $key, 'label' => $key, ...$attributes]);
                    }
                }
            }

            $shipping = 'ارسال سفارش‌ها به سراسر ایران با تیپاکس انجام می‌شود و هزینه حمل با مشتری است. ثبت مرسوله پس از پرداخت انجام می‌شود و بارکد یا پیام تیپاکس در اختیار مشتری قرار می‌گیرد. خرید حضوری نیز امکان دارد. تعویض سایز و مدل پذیرفته می‌شود؛ برای اطلاع از مهلت، هزینه و شرایط دقیق، پیش از خرید با مجموعه هماهنگ کنید.';
            $pages = [
                'about' => ['title' => 'درباره NOX', 'content' => $story, 'excerpt' => 'داستان NOX Collection؛ از استایل شخصی تا پوشاک استریت‌ور.'],
                'contact' => ['title' => 'تماس با NOX', 'content' => $address.' — '.implode('؛ ', $hours).' — تلفن: 09309599920 و 09101872731', 'excerpt' => 'اطلاعات تماس و ساعت کاری فروشگاه حضوری NOX در تهران.'],
                'shipping-returns' => ['title' => 'ارسال و تعویض NOX', 'content' => $shipping, 'excerpt' => 'ارسال سراسری با تیپاکس و اطلاعات تأییدشده تعویض سایز و مدل.'],
            ];
            foreach ($pages as $slug => $page) {
                $model = ContentPage::query()->firstOrNew(['slug' => $slug]);
                if (! $model->exists) {
                    $model->public_id = (string) Str::ulid();
                }
                $model->fill([
                    'type' => 'page', 'title' => $page['title'], 'excerpt' => $page['excerpt'],
                    'content' => '<p>'.e($page['content']).'</p>',
                    'meta_title' => $page['title'].' | NOX Collection', 'meta_description' => $page['excerpt'],
                    'status' => 'published', 'published_at' => now(),
                ])->save();
            }
        });

        $this->command?->info('NOX brand settings saved. Backup: '.$backupPath);
        $this->command?->warn('Products, prices, inventory, shipping rates, and payment activation were not changed.');
    }

    private function localize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->localize($item), $value);
        }
        if (! is_string($value)) {
            return $value;
        }

        return str_replace(
            ['ال‌بی‌بی', 'LBB', 'پاساژ مهستان', 'مهستان', 'کرج', 'البرز', '@lbbclo', 'instagram.com/lbbclo'],
            ['نوکس', 'NOX', 'پاساژ فردوسی', 'فردوسی', 'تهران', 'تهران', '@collection_nox', 'instagram.com/collection_nox'],
            $value,
        );
    }
}
