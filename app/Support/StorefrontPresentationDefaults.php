<?php

namespace App\Support;

final class StorefrontPresentationDefaults
{
    /**
     * Public presentation defaults used only when a setting has not been saved yet.
     * Existing StoreSetting rows always win. Legal/commercial claims intentionally
     * default to disabled or pending states and are never invented here.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function settings(): array
    {
        return [
            'announcement' => [
                'announcement.messages' => [
                    ['text' => 'LBB؛ الهام‌گرفته از ذهنی خلاق', 'href' => '/shop', 'enabled' => true],
                    ['text' => 'فروشگاه حضوری LBB — کرج، پاساژ مهستان', 'href' => '/contact', 'enabled' => true],
                    ['text' => 'راهنمای سایز اختصاصی برای انتخاب دقیق‌تر', 'href' => '/size-guide', 'enabled' => true],
                ],
            ],
            'navigation' => [
                'navigation.shop' => [
                    ['label' => 'همه محصولات', 'latin' => 'SHOP ALL', 'description' => 'مشاهده کامل کاتالوگ', 'href' => '/shop', 'enabled' => true],
                ],
                'navigation.editorial' => [
                    ['label' => 'کالکشن‌ها', 'latin' => 'COLLECTIONS', 'description' => 'دراپ‌ها و فصل‌های LBB', 'href' => '/collections', 'enabled' => true],
                    ['label' => 'لوک‌بوک', 'latin' => 'LOOKBOOK', 'description' => 'استایل‌ها در بافت شهری', 'href' => '/lookbook', 'enabled' => true],
                    ['label' => 'ژورنال', 'latin' => 'JOURNAL', 'description' => 'راهنما، فرهنگ و متریال', 'href' => '/journal', 'enabled' => true],
                ],
                'navigation.service' => [
                    ['label' => 'راهنمای سایز', 'latin' => 'SIZE GUIDE', 'href' => '/size-guide', 'enabled' => true],
                    ['label' => 'ارسال و مرجوعی', 'latin' => 'SHIPPING', 'href' => '/shipping-returns', 'enabled' => true],
                    ['label' => 'پیگیری سفارش', 'latin' => 'TRACK ORDER', 'href' => '/track-order', 'enabled' => true],
                    ['label' => 'سوالات متداول', 'latin' => 'FAQ', 'href' => '/faq', 'enabled' => true],
                    ['label' => 'تماس', 'latin' => 'CONTACT', 'href' => '/contact', 'enabled' => true],
                ],
                'navigation.brand' => [
                    ['label' => 'درباره LBB', 'latin' => 'ABOUT', 'href' => '/about', 'enabled' => true],
                    ['label' => 'قوانین', 'latin' => 'TERMS', 'href' => '/terms', 'enabled' => true],
                    ['label' => 'حریم خصوصی', 'latin' => 'PRIVACY', 'href' => '/privacy', 'enabled' => true],
                ],
            ],
            'home' => [
                'home.brand_intro' => [
                    'enabled' => true,
                    'version' => 'v1',
                    'eyebrow' => 'LBB / STREETWEAR',
                    'title' => 'از رگال تا فروشگاه',
                    'body' => 'LBB؛ الهام‌گرفته از ذهنی خلاق',
                    'storyCta' => 'داستان LBB',
                    'storeCta' => 'ورود به فروشگاه',
                ],
                'home.presentation' => [
                    'heroEnabled' => true,
                    'heroProductSlug' => '',
                    'heroImagePath' => null,
                    'heroImageUrl' => null,
                    'heroImageAlt' => '',
                    'heroImageFit' => 'contain',
                    'heroImagePosition' => 'center center',
                    'primaryCtaHref' => '/shop',
                    'secondaryCtaHref' => '/contact',
                    'categoryOrder' => [],
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
                    'productCuration' => [
                        'mode' => 'newest',
                        'count' => 4,
                        'manualProductSlugs' => [],
                    ],
                ],
                'home.ticker' => [
                    'ال‌بی‌بی',
                    'پوشاک خیابانی',
                    'تیشرت',
                    'شلوار',
                    'کتونی',
                    'جوراب',
                    'کرج',
                    'پاساژ مهستان',
                ],
                'home.trust' => [
                    [
                        'icon' => 'details',
                        'title' => 'جزئیات روشن محصول',
                        'description' => 'جنس، تن‌خور، رنگ و ویژگی‌های هر قطعه پیش از انتخاب در دسترس است',
                        'href' => '/shop',
                        'enabled' => true,
                    ],
                    [
                        'icon' => 'size',
                        'title' => 'انتخاب آگاهانه اندازه',
                        'description' => 'راهنمای اندازه و توضیح تن‌خور برای مقایسه و انتخاب دقیق‌تر',
                        'href' => '/size-guide',
                        'enabled' => true,
                    ],
                    [
                        'icon' => 'location',
                        'title' => 'حضور در کرج',
                        'description' => 'فروشگاه حضوری LBB در پاساژ مهستان کرج',
                        'href' => '/contact',
                        'enabled' => true,
                    ],
                ],
                'home.section_copy' => [
                    'hero' => [
                        'helperText' => 'قیمت، رنگ، سایزهای موجود و تن‌خور هر محصول را قبل از انتخاب بررسی کن.',
                        'categoryPrompt' => 'دسته موردنظرت را سریع پیدا کن:',
                        'categoryAnchorLabel' => 'دیدن دسته‌بندی‌ها',
                    ],
                    'categories' => [
                        'label' => 'دسته‌بندی محصولات',
                        'title' => 'دنبال چی می‌گردی؟',
                        'lede' => 'دسته‌های منتخب فروشگاه را مرور کن و مستقیم وارد مدل‌های همان بخش شو.',
                        'actionLabel' => 'همه محصولات',
                        'actionHref' => '/shop',
                    ],
                    'products' => [
                        'label' => 'انتخاب‌های ال‌بی‌بی',
                        'title' => 'تازه‌های فروشگاه',
                        'lede' => 'محصولات تازه منتشرشده فروشگاه با قیمت و موجودی زنده.',
                        'actionLabel' => 'کاتالوگ کامل',
                        'actionHref' => '/shop',
                    ],
                    'instagram' => [
                        'followCta' => 'دنبال ما در اینستاگرام',
                        'lookbookCta' => 'مشاهده لوک‌بوک',
                        'lookbookHref' => '/lookbook',
                    ],
                ],
                'home.decision_support' => [
                    'enabled' => true,
                    'label' => 'راهنمای انتخاب',
                    'title' => 'قبل از انتخاب، جواب‌ها را داشته باش',
                    'lede' => 'اگر بین دو سایز یا مدل مرددی، این راهنماها انتخاب را سریع‌تر و مطمئن‌تر می‌کنند.',
                    'checkLabel' => 'قبل از خرید بررسی کن',
                    'checks' => [
                        'تن‌خور و جدول اندازه را پیش از انتخاب ببین',
                        'رنگ و سایزهای موجود هر محصول را همان لحظه بررسی کن',
                        'جنس پارچه و روش نگهداری را در صفحه محصول بخوان',
                        'برای پرو و خرید حضوری به فروشگاه مهستان سر بزن',
                    ],
                    'links' => [
                        [
                            'label' => 'راهنمای انتخاب سایز',
                            'latin' => 'SIZE / FIT',
                            'description' => 'قبل از خرید، فیت و اندازه مناسب را مقایسه کن.',
                            'href' => '/size-guide',
                        ],
                        [
                            'label' => 'ارسال و مرجوعی',
                            'latin' => 'DELIVERY / RETURNS',
                            'description' => 'پیش از سفارش، شرایط ارسال و امکان مرجوعی را بررسی کن.',
                            'href' => '/shipping-returns',
                        ],
                        [
                            'label' => 'پارچه و نگهداری',
                            'latin' => 'MATERIAL / CARE',
                            'description' => 'گرماژ، ترکیب پارچه و روش شست‌وشوی هر قطعه.',
                            'href' => '/journal/materials-101-parche-shenasi',
                        ],
                    ],
                ],
                'home.local_store' => [
                    'enabled' => true,
                    'eyebrow' => 'فروشگاه حضوری ال‌بی‌بی',
                    'title' => 'آنلاین ببین، در مهستان از نزدیک انتخاب کن.',
                    'body' => 'مدل‌ها را در سایت مقایسه کن و اگر دوست داشتی برای دیدن رنگ، جنس و تن‌خور از نزدیک به فروشگاه ال‌بی‌بی در پاساژ مهستان کرج سر بزن.',
                    'imagePath' => null,
                    'imageUrl' => null,
                    'addressTitle' => 'آدرس فروشگاه',
                    'instagramTitle' => 'مدل‌های تازه در اینستاگرام',
                    'contactCta' => 'اطلاعات تماس و مراجعه',
                    'contactHref' => '/contact',
                    'instagramCta' => 'پیام در اینستاگرام',
                    'instagramHref' => null,
                    'shopCta' => 'قبل از مراجعه محصولات را ببین',
                    'shopHref' => '/shop',
                ],
                'home.featured_story' => [
                    'enabled' => false,
                    'collectionSlug' => '',
                    'eyebrow' => 'استایل پیشنهادی ال‌بی‌بی',
                    'storyPoints' => [],
                    'collectionCta' => 'دیدن کالکشن',
                    'lookbookCta' => 'ایده‌های بیشتر برای استایل',
                    'inventoryNote' => 'برای دیدن موجودی و سایزهای هر قطعه وارد صفحه همان محصول شو.',
                ],
            ],
            'shell' => [
                'shell.copy' => [
                    'shopMenuLabel' => 'فروشگاه',
                    'footerGroups' => [
                        'shop' => 'خرید',
                        'editorial' => 'کالکشن و محتوا',
                        'service' => 'پشتیبانی',
                        'personal' => 'شخصی',
                        'brand' => 'برند',
                    ],
                    'utilityLinks' => [
                        ['label' => 'قوانین', 'href' => '/terms', 'enabled' => true],
                        ['label' => 'حریم خصوصی', 'href' => '/privacy', 'enabled' => true],
                        ['label' => 'تماس', 'href' => '/contact', 'enabled' => true],
                    ],
                ],
            ],
            'page' => [
                'page.presentation' => [
                    'shop' => [
                        'metaTitle' => 'فروشگاه LBB | خرید پوشاک خیابانی و استریت‌ویر',
                        'metaDescription' => 'محصولات موجود LBB را با فیلترهای دسته، سایز، رنگ، قیمت و موجودی مرور کن و برای جزئیات هر مدل وارد صفحه همان محصول شو.',
                        'eyebrow' => 'فروشگاه / ال‌بی‌بی / مهستان',
                        'title' => 'استایل تو، قانون تو.',
                        'lede' => 'قطعه‌های ال‌بی‌بی را بر اساس دسته، رنگ، سایز و موجودی کشف کن؛ یا مستقیم چیزی را که می‌خواهی جست‌وجو کن.',
                        'sectionLabel' => 'SHOP BY CATEGORY',
                        'sectionTitle' => 'از دسته شروع کن',
                        'primaryCtaLabel' => '',
                        'primaryCtaHref' => '',
                        'secondaryCtaLabel' => '',
                        'secondaryCtaHref' => '',
                        'socialImagePath' => null,
                        'socialImageUrl' => null,
                    ],
                    'collections' => [
                        'metaTitle' => 'کالکشن‌های LBB | روایت‌های ادیتوریال و مسیرهای کشف',
                        'metaDescription' => 'کالکشن‌های منتشرشده LBB و مسیرهای کشف محصول.',
                        'eyebrow' => 'LBB / PUBLISHED COLLECTIONS',
                        'title' => 'کالکشن‌های منتشرشده',
                        'lede' => 'این فهرست از Backend می‌آید. فقط کالکشن و عضویت محصولی که برای انتشار معتبر است نمایش داده می‌شود.',
                        'sectionLabel' => '',
                        'sectionTitle' => '',
                        'primaryCtaLabel' => 'مرور فروشگاه',
                        'primaryCtaHref' => '/shop',
                        'secondaryCtaLabel' => 'لوک‌بوک ادیتوریال',
                        'secondaryCtaHref' => '/lookbook',
                        'socialImagePath' => null,
                        'socialImageUrl' => null,
                    ],
                    'lookbook' => [
                        'metaTitle' => 'لوک‌بوک LBB | داستان‌های تصویری و مسیرهای مرتبط',
                        'metaDescription' => 'لوک‌بوک LBB را ببینید؛ داستان‌های تصویری با مسیرهای مرتبط به کالکشن، دسته و در صورت انتشار عمومی، صفحه محصول.',
                        'eyebrow' => 'LBB / VISUAL STORIES',
                        'title' => 'لوک‌بوک؛ داستان‌های تصویری LBB',
                        'lede' => 'تصاویر و مسیرهای این صفحه مستقیماً از محتوای منتشرشده فروشگاه می‌آیند.',
                        'sectionLabel' => '',
                        'sectionTitle' => '',
                        'primaryCtaLabel' => '',
                        'primaryCtaHref' => '',
                        'secondaryCtaLabel' => '',
                        'secondaryCtaHref' => '',
                        'socialImagePath' => null,
                        'socialImageUrl' => null,
                    ],
                    'journal' => [
                        'metaTitle' => 'ژورنال LBB | راهنمای استایل، پارچه و نگهداری',
                        'metaDescription' => 'ژورنال LBB: راهنماهای کاربردی درباره استایل، ترکیب رنگ، شناخت پارچه و نگهداری، همراه با مسیرهای مرتبط به کالکشن و دسته‌های فروشگاه.',
                        'eyebrow' => 'LBB / EDITORIAL NOTES',
                        'title' => 'ژورنال؛ راهنماها و یادداشت‌های منتشرشده LBB',
                        'lede' => 'فهرست این صفحه مستقیماً از محتوای منتشرشده در Backend می‌آید.',
                        'sectionLabel' => '',
                        'sectionTitle' => '',
                        'primaryCtaLabel' => '',
                        'primaryCtaHref' => '',
                        'secondaryCtaLabel' => '',
                        'secondaryCtaHref' => '',
                        'socialImagePath' => null,
                        'socialImageUrl' => null,
                    ],
                    'faq' => [
                        'metaTitle' => 'سوالات متداول LBB | سایز، ارسال و انتخاب محصول',
                        'metaDescription' => 'پاسخ سوالات متداول LBB درباره اطلاعات محصول، انتخاب سایز و روش‌های ارسال تأییدشده؛ در حالت live پاسخ‌ها از پنل مدیریت می‌آیند.',
                        'eyebrow' => 'FAQ / LBB',
                        'title' => 'پاسخ‌های روشن پیش از انتخاب و ثبت سفارش',
                        'lede' => 'در حالت live، فقط سؤال‌های فعال ثبت‌شده در پنل مدیریت نمایش داده می‌شوند.',
                        'sectionLabel' => '',
                        'sectionTitle' => '',
                        'primaryCtaLabel' => '',
                        'primaryCtaHref' => '',
                        'secondaryCtaLabel' => '',
                        'secondaryCtaHref' => '',
                        'socialImagePath' => null,
                        'socialImageUrl' => null,
                    ],
                ],
            ],
            'faq' => [
                'faq.presentation' => [
                    'products' => ['label' => 'PRODUCT DATA', 'title' => 'محصول و موجودی'],
                    'sizing' => ['label' => 'FIT & SIZE', 'title' => 'فیت و انتخاب سایز'],
                    'ordering' => ['label' => 'ORDER FLOW', 'title' => 'قیمت و ثبت سفارش'],
                    'shipping' => ['label' => 'SHIPPING', 'title' => 'ارسال و تحویل'],
                    'returns' => ['label' => 'RETURNS', 'title' => 'تعویض و مرجوعی'],
                    'editorial' => ['label' => 'EDITORIAL', 'title' => 'محتوا و راهنما'],
                    'general' => ['label' => 'GENERAL', 'title' => 'سوالات عمومی'],
                ],
            ],
            'policy' => [
                'policy.returns' => [
                    'enabled' => false,
                    'verification' => 'pending',
                    'exchangeEnabled' => false,
                    'returnWindowDays' => null,
                    'refundTimeLabel' => null,
                    'customerPaysReturnShipping' => null,
                    'quickIssueNoticeHours' => null,
                ],
            ],
            'trust' => [
                'trust.enamad' => [
                    'enabled' => false,
                    'verification' => 'missing',
                    'identifier' => null,
                    'verificationUrl' => null,
                    'badgeImageUrl' => null,
                    'altText' => 'نماد اعتماد الکترونیکی LBB',
                    'displayLocation' => 'footer',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function contactDefaults(): array
    {
        return [
            'email' => null,
            'addressLine' => null,
            'mapUrl' => null,
            'openingHours' => [],
        ];
    }

    /**
     * Existing saved list values remain authoritative. Associative objects are
     * filled recursively so newly-added presentation keys become available
     * without forcing a merchant save or a business-data migration.
     *
     * @param  array<string, array<string, mixed>>  $settings
     * @return array<string, array<string, mixed>>
     */
    public static function merge(array $settings): array
    {
        foreach (self::settings() as $group => $items) {
            foreach ($items as $key => $default) {
                if (! array_key_exists($key, $settings[$group] ?? [])) {
                    $settings[$group][$key] = $default;

                    continue;
                }

                $current = $settings[$group][$key];
                if (is_array($default) && is_array($current) && ! array_is_list($default)) {
                    $settings[$group][$key] = self::mergeObjectDefaults($default, $current);
                }
            }
        }

        $contact = $settings['contact']['contact.public'] ?? null;
        if (is_array($contact)) {
            $settings['contact']['contact.public'] = array_replace(self::contactDefaults(), $contact);
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    private static function mergeObjectDefaults(array $defaults, array $current): array
    {
        foreach ($defaults as $key => $default) {
            if (! array_key_exists($key, $current)) {
                $current[$key] = $default;

                continue;
            }

            if (is_array($default) && is_array($current[$key]) && ! array_is_list($default)) {
                $current[$key] = self::mergeObjectDefaults($default, $current[$key]);
            }
        }

        return $current;
    }
}
