<?php

namespace App\Filament\Pages;

use App\Models\StoreSetting;
use App\Support\StorefrontPresentationDefaults;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'تنظیمات سایت';

    protected static ?string $title = 'تنظیمات عمومی و صفحه اصلی';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.site-settings';

    public array $data = [];

    public function mount(): void
    {
        $defaults = StorefrontPresentationDefaults::settings();
        $brand = $this->jsonSetting('brand', 'brand.identity');
        $copy = $this->jsonSetting('brand', 'brand.copy');
        $contact = array_replace(
            StorefrontPresentationDefaults::contactDefaults(),
            $this->jsonSetting('contact', 'contact.public'),
        );
        $intro = $this->jsonSetting('home', 'home.brand_intro');
        $home = $this->jsonSetting('home', 'home.presentation');
        $ticker = $this->jsonSetting('home', 'home.ticker', $defaults['home']['home.ticker']);
        $trust = $this->jsonSetting('home', 'home.trust', $defaults['home']['home.trust']);
        $sectionCopy = $this->jsonSetting('home', 'home.section_copy', $defaults['home']['home.section_copy']);
        $decision = $this->jsonSetting('home', 'home.decision_support', $defaults['home']['home.decision_support']);
        $localStore = $this->jsonSetting('home', 'home.local_store', $defaults['home']['home.local_store']);
        $featuredStory = $this->jsonSetting('home', 'home.featured_story', $defaults['home']['home.featured_story']);
        $returns = $this->jsonSetting('policy', 'policy.returns', $defaults['policy']['policy.returns']);
        $enamad = $this->jsonSetting('trust', 'trust.enamad', $defaults['trust']['trust.enamad']);
        $seo = $this->jsonSetting('seo', 'seo.defaults');

        $heroCopy = is_array($sectionCopy['hero'] ?? null) ? $sectionCopy['hero'] : [];
        $categoryCopy = is_array($sectionCopy['categories'] ?? null) ? $sectionCopy['categories'] : [];
        $productCopy = is_array($sectionCopy['products'] ?? null) ? $sectionCopy['products'] : [];
        $instagramCopy = is_array($sectionCopy['instagram'] ?? null) ? $sectionCopy['instagram'] : [];

        $this->data = [
            'brand_name' => $brand['name'] ?? 'LBB',
            'brand_name_fa' => $brand['nameFa'] ?? 'LBB',
            'brand_category' => $brand['category'] ?? '',
            'brand_city' => $brand['city'] ?? 'کرج',
            'brand_province' => $brand['province'] ?? 'البرز',
            'brand_location' => $brand['physicalLocation'] ?? '',
            'brand_location_short' => $brand['physicalLocationShort'] ?? '',
            'brand_instagram_handle' => $brand['instagramHandle'] ?? '',
            'brand_instagram_url' => $brand['instagramUrl'] ?? '',
            'brand_slogan' => $brand['slogan'] ?? '',
            'brand_story_title' => $brand['storyTitle'] ?? '',
            'brand_descriptor' => $brand['descriptor'] ?? '',
            'brand_short_introduction' => $brand['shortIntroduction'] ?? '',
            'homepage_title' => $copy['homepageTitle'] ?? '',
            'homepage_description' => $copy['homepageDescription'] ?? '',
            'hero_eyebrow' => $copy['heroEyebrow'] ?? '',
            'hero_title' => $copy['heroTitle'] ?? '',
            'hero_body' => $copy['heroBody'] ?? '',
            'primary_cta' => $copy['primaryCta'] ?? '',
            'secondary_cta' => $copy['secondaryCta'] ?? '',
            'store_location_label' => $copy['storeLocationLabel'] ?? '',
            'contact_phone' => $contact['phone'] ?? '',
            'contact_whatsapp' => $contact['whatsapp'] ?? '',
            'contact_email' => $contact['email'] ?? '',
            'contact_address_line' => $contact['addressLine'] ?? '',
            'contact_map_url' => $contact['mapUrl'] ?? '',
            'contact_opening_hours' => is_array($contact['openingHours'] ?? null) ? $contact['openingHours'] : [],
            'intro_eyebrow' => $intro['eyebrow'] ?? '',
            'intro_title' => $intro['title'] ?? '',
            'intro_body' => $intro['body'] ?? '',
            'intro_story_cta' => $intro['storyCta'] ?? '',
            'intro_store_cta' => $intro['storeCta'] ?? '',
            'hero_product_slug' => $home['heroProductSlug'] ?? '',
            'category_order' => $home['categoryOrder'] ?? [],
            'home_sections' => $home['sections'] ?? [],
            'hero_helper_text' => $heroCopy['helperText'] ?? '',
            'hero_category_prompt' => $heroCopy['categoryPrompt'] ?? '',
            'hero_category_anchor_label' => $heroCopy['categoryAnchorLabel'] ?? '',
            'category_section_label' => $categoryCopy['label'] ?? '',
            'category_section_title' => $categoryCopy['title'] ?? '',
            'category_section_lede' => $categoryCopy['lede'] ?? '',
            'category_section_action' => $categoryCopy['actionLabel'] ?? '',
            'product_section_label' => $productCopy['label'] ?? '',
            'product_section_title' => $productCopy['title'] ?? '',
            'product_section_lede' => $productCopy['lede'] ?? '',
            'product_section_action' => $productCopy['actionLabel'] ?? '',
            'instagram_follow_cta' => $instagramCopy['followCta'] ?? '',
            'instagram_lookbook_cta' => $instagramCopy['lookbookCta'] ?? '',
            'ticker_items' => collect($ticker)->filter(fn ($item): bool => is_string($item) && trim($item) !== '')
                ->map(fn (string $item): array => ['text' => $item])->values()->all(),
            'trust_items' => collect($trust)->filter(fn ($value): bool => is_array($value))->values()->all(),
            'decision_enabled' => (bool) ($decision['enabled'] ?? true),
            'decision_label' => $decision['label'] ?? '',
            'decision_title' => $decision['title'] ?? '',
            'decision_lede' => $decision['lede'] ?? '',
            'decision_check_label' => $decision['checkLabel'] ?? '',
            'decision_checks' => collect($decision['checks'] ?? [])->filter(fn ($item): bool => is_string($item) && trim($item) !== '')
                ->map(fn (string $item): array => ['text' => $item])->values()->all(),
            'decision_links' => collect($decision['links'] ?? [])->filter(fn ($value): bool => is_array($value))->values()->all(),
            'local_store_enabled' => (bool) ($localStore['enabled'] ?? true),
            'local_store_eyebrow' => $localStore['eyebrow'] ?? '',
            'local_store_title' => $localStore['title'] ?? '',
            'local_store_body' => $localStore['body'] ?? '',
            'local_store_image_url' => $localStore['imageUrl'] ?? '',
            'local_store_address_title' => $localStore['addressTitle'] ?? '',
            'local_store_instagram_title' => $localStore['instagramTitle'] ?? '',
            'local_store_contact_cta' => $localStore['contactCta'] ?? '',
            'local_store_instagram_cta' => $localStore['instagramCta'] ?? '',
            'local_store_shop_cta' => $localStore['shopCta'] ?? '',
            'featured_story_enabled' => (bool) ($featuredStory['enabled'] ?? false),
            'featured_story_collection_slug' => $featuredStory['collectionSlug'] ?? '',
            'featured_story_eyebrow' => $featuredStory['eyebrow'] ?? '',
            'featured_story_points' => collect($featuredStory['storyPoints'] ?? [])->filter(fn ($item): bool => is_string($item) && trim($item) !== '')
                ->map(fn (string $item): array => ['text' => $item])->values()->all(),
            'featured_story_collection_cta' => $featuredStory['collectionCta'] ?? '',
            'featured_story_lookbook_cta' => $featuredStory['lookbookCta'] ?? '',
            'featured_story_inventory_note' => $featuredStory['inventoryNote'] ?? '',
            'returns_enabled' => (bool) ($returns['enabled'] ?? false),
            'returns_verification' => $returns['verification'] ?? 'pending',
            'returns_exchange_enabled' => (bool) ($returns['exchangeEnabled'] ?? false),
            'returns_window_days' => $returns['returnWindowDays'] ?? null,
            'returns_refund_time_label' => $returns['refundTimeLabel'] ?? '',
            'returns_customer_pays_shipping' => $returns['customerPaysReturnShipping'] ?? null,
            'returns_quick_issue_notice_hours' => $returns['quickIssueNoticeHours'] ?? null,
            'enamad_enabled' => (bool) ($enamad['enabled'] ?? false),
            'enamad_verification' => $enamad['verification'] ?? 'missing',
            'enamad_identifier' => $enamad['identifier'] ?? '',
            'enamad_verification_url' => $enamad['verificationUrl'] ?? '',
            'enamad_badge_image_url' => $enamad['badgeImageUrl'] ?? '',
            'enamad_alt_text' => $enamad['altText'] ?? 'نماد اعتماد الکترونیکی LBB',
            'enamad_display_location' => $enamad['displayLocation'] ?? 'footer',
            'seo_site_name' => $seo['siteName'] ?? 'LBB',
            'seo_locale' => $seo['locale'] ?? 'fa_IR',
            'seo_description' => $seo['organizationDescription'] ?? '',
            'seo_instagram_url' => $seo['instagramUrl'] ?? '',
        ];

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('تنظیمات')->tabs([
                    Tabs\Tab::make('هویت برند')->schema([
                        TextInput::make('brand_name')->label('نام لاتین برند')->required(),
                        TextInput::make('brand_name_fa')->label('نام نمایشی برند')->required(),
                        TextInput::make('brand_category')->label('دسته/جایگاه برند'),
                        TextInput::make('brand_city')->label('شهر'),
                        TextInput::make('brand_province')->label('استان'),
                        TextInput::make('brand_location')->label('آدرس/موقعیت کامل')->columnSpanFull(),
                        TextInput::make('brand_location_short')->label('موقعیت کوتاه'),
                        TextInput::make('brand_instagram_handle')->label('نام کاربری اینستاگرام'),
                        TextInput::make('brand_instagram_url')->label('لینک اینستاگرام')->url(),
                        TextInput::make('brand_slogan')->label('شعار برند')->columnSpanFull(),
                        TextInput::make('brand_story_title')->label('عنوان داستان برند'),
                        TextInput::make('brand_descriptor')->label('Descriptor'),
                        Textarea::make('brand_short_introduction')->label('معرفی کوتاه')->rows(3)->columnSpanFull(),
                    ])->columns(2),
                    Tabs\Tab::make('Hero و صفحه اصلی')->schema([
                        TextInput::make('homepage_title')->label('Title صفحه اصلی')->columnSpanFull(),
                        Textarea::make('homepage_description')->label('Description صفحه اصلی')->rows(2)->columnSpanFull(),
                        TextInput::make('hero_eyebrow')->label('Eyebrow هیرو'),
                        TextInput::make('hero_title')->label('عنوان هیرو'),
                        Textarea::make('hero_body')->label('متن هیرو')->rows(3)->columnSpanFull(),
                        TextInput::make('primary_cta')->label('CTA اصلی'),
                        TextInput::make('secondary_cta')->label('CTA دوم'),
                        TextInput::make('store_location_label')->label('برچسب موقعیت فروشگاه'),
                        TextInput::make('hero_product_slug')->label('Slug محصول Hero')->helperText('فقط محصول واقعی منتشرشده را وارد کنید.'),
                        TagsInput::make('category_order')->label('ترتیب Slug دسته‌ها')->columnSpanFull(),
                        TagsInput::make('home_sections')->label('ترتیب سکشن‌های خانه')->columnSpanFull(),
                        Textarea::make('hero_helper_text')->label('متن راهنمای Hero')->rows(2)->columnSpanFull(),
                        TextInput::make('hero_category_prompt')->label('عنوان دسترسی سریع دسته‌ها'),
                        TextInput::make('hero_category_anchor_label')->label('متن لینک دسته‌بندی‌ها'),
                        TextInput::make('category_section_label')->label('برچسب سکشن دسته‌ها'),
                        TextInput::make('category_section_title')->label('عنوان سکشن دسته‌ها'),
                        Textarea::make('category_section_lede')->label('توضیح سکشن دسته‌ها')->rows(2)->columnSpanFull(),
                        TextInput::make('category_section_action')->label('CTA سکشن دسته‌ها'),
                        TextInput::make('product_section_label')->label('برچسب سکشن محصولات'),
                        TextInput::make('product_section_title')->label('عنوان سکشن محصولات'),
                        Textarea::make('product_section_lede')->label('توضیح سکشن محصولات')->rows(2)->columnSpanFull(),
                        TextInput::make('product_section_action')->label('CTA سکشن محصولات'),
                        TextInput::make('instagram_follow_cta')->label('CTA اینستاگرام'),
                        TextInput::make('instagram_lookbook_cta')->label('CTA لوک‌بوک'),
                    ])->columns(2),
                    Tabs\Tab::make('نوار و اعتماد')->schema([
                        Repeater::make('ticker_items')->label('عبارت‌های نوار متحرک')->schema([
                            TextInput::make('text')->label('عبارت')->required()->maxLength(100),
                        ])->minItems(1)->maxItems(20)->reorderable()->columnSpanFull(),
                        Repeater::make('trust_items')->label('کارت‌های اعتماد/راهنما')->schema([
                            Select::make('icon')->label('آیکن')->options([
                                'details' => 'جزئیات محصول',
                                'size' => 'اندازه',
                                'location' => 'موقعیت',
                                'shipping' => 'ارسال',
                                'support' => 'پشتیبانی',
                            ])->required(),
                            TextInput::make('title')->label('عنوان')->required()->maxLength(120),
                            Textarea::make('description')->label('توضیح')->rows(2)->required()->columnSpanFull(),
                            TextInput::make('href')->label('لینک داخلی یا HTTPS')->maxLength(500),
                            Toggle::make('enabled')->label('نمایش')->default(true),
                        ])->columns(2)->maxItems(8)->reorderable()->columnSpanFull(),
                    ]),
                    Tabs\Tab::make('راهنمای انتخاب')->schema([
                        Toggle::make('decision_enabled')->label('نمایش سکشن')->default(true),
                        TextInput::make('decision_label')->label('برچسب'),
                        TextInput::make('decision_title')->label('عنوان'),
                        Textarea::make('decision_lede')->label('توضیح')->rows(2)->columnSpanFull(),
                        TextInput::make('decision_check_label')->label('عنوان چک‌لیست'),
                        Repeater::make('decision_checks')->label('چک‌لیست')->schema([
                            TextInput::make('text')->label('مورد')->required()->maxLength(220),
                        ])->maxItems(10)->reorderable()->columnSpanFull(),
                        Repeater::make('decision_links')->label('کارت‌های راهنما')->schema([
                            TextInput::make('label')->label('عنوان')->required()->maxLength(120),
                            TextInput::make('latin')->label('برچسب لاتین')->maxLength(80),
                            Textarea::make('description')->label('توضیح')->rows(2)->columnSpanFull(),
                            TextInput::make('href')->label('مسیر داخلی یا HTTPS')->required()->maxLength(500),
                        ])->columns(2)->maxItems(6)->reorderable()->columnSpanFull(),
                    ])->columns(2),
                    Tabs\Tab::make('فروشگاه و Story')->schema([
                        Toggle::make('local_store_enabled')->label('نمایش سکشن فروشگاه حضوری')->default(true),
                        TextInput::make('local_store_eyebrow')->label('Eyebrow فروشگاه'),
                        TextInput::make('local_store_title')->label('عنوان فروشگاه')->columnSpanFull(),
                        Textarea::make('local_store_body')->label('متن فروشگاه')->rows(3)->columnSpanFull(),
                        TextInput::make('local_store_image_url')->label('تصویر HTTPS اختیاری')->url()->columnSpanFull(),
                        TextInput::make('local_store_address_title')->label('عنوان آدرس'),
                        TextInput::make('local_store_instagram_title')->label('عنوان اینستاگرام'),
                        TextInput::make('local_store_contact_cta')->label('CTA تماس'),
                        TextInput::make('local_store_instagram_cta')->label('CTA اینستاگرام'),
                        TextInput::make('local_store_shop_cta')->label('CTA فروشگاه'),
                        Toggle::make('featured_story_enabled')->label('نمایش Featured Collection Story')->default(false),
                        TextInput::make('featured_story_collection_slug')->label('Slug کالکشن واقعی')->helperText('در حالت فعال، کالکشن و محصولات از API واقعی خوانده می‌شوند.'),
                        TextInput::make('featured_story_eyebrow')->label('Eyebrow Story'),
                        Repeater::make('featured_story_points')->label('نکته‌های Story')->schema([
                            TextInput::make('text')->label('نکته')->required()->maxLength(220),
                        ])->maxItems(8)->reorderable()->columnSpanFull(),
                        TextInput::make('featured_story_collection_cta')->label('CTA کالکشن'),
                        TextInput::make('featured_story_lookbook_cta')->label('CTA لوک‌بوک'),
                        Textarea::make('featured_story_inventory_note')->label('یادداشت موجودی')->rows(2)->columnSpanFull(),
                    ])->columns(2),
                    Tabs\Tab::make('معرفی برند')->schema([
                        TextInput::make('intro_eyebrow')->label('Eyebrow'),
                        TextInput::make('intro_title')->label('عنوان'),
                        Textarea::make('intro_body')->label('متن')->rows(3)->columnSpanFull(),
                        TextInput::make('intro_story_cta')->label('CTA داستان'),
                        TextInput::make('intro_store_cta')->label('CTA فروشگاه'),
                    ])->columns(2),
                    Tabs\Tab::make('ارتباط')->schema([
                        TextInput::make('contact_phone')->label('تلفن عمومی'),
                        TextInput::make('contact_whatsapp')->label('واتساپ عمومی'),
                        TextInput::make('contact_email')->label('ایمیل عمومی')->email(),
                        TextInput::make('contact_address_line')->label('آدرس نمایشی')->columnSpanFull(),
                        TextInput::make('contact_map_url')->label('لینک نقشه HTTPS')->url()->columnSpanFull(),
                        TagsInput::make('contact_opening_hours')->label('ساعت‌های کاری')->helperText('هر بازه را یک مورد جدا ثبت کنید.')->columnSpanFull(),
                    ])->columns(2),
                    Tabs\Tab::make('سیاست و اعتماد')->schema([
                        Toggle::make('returns_enabled')->label('فعال بودن سیاست مرجوعی'),
                        Select::make('returns_verification')->label('وضعیت تأیید مرجوعی')->options([
                            'missing' => 'ثبت نشده',
                            'pending' => 'در حال بررسی',
                            'verified' => 'تأییدشده',
                        ])->required(),
                        Toggle::make('returns_exchange_enabled')->label('تعویض فعال'),
                        TextInput::make('returns_window_days')->label('مهلت درخواست (روز)')->numeric()->minValue(0)->maxValue(365),
                        TextInput::make('returns_refund_time_label')->label('متن زمان بازپرداخت'),
                        Select::make('returns_customer_pays_shipping')->label('هزینه ارسال برگشت با مشتری؟')->options([
                            '1' => 'بله',
                            '0' => 'خیر',
                        ])->placeholder('تعیین نشده'),
                        TextInput::make('returns_quick_issue_notice_hours')->label('مهلت اعلام سریع مشکل (ساعت)')->numeric()->minValue(1)->maxValue(720),
                        Toggle::make('enamad_enabled')->label('نمایش Enamad'),
                        Select::make('enamad_verification')->label('وضعیت تأیید Enamad')->options([
                            'missing' => 'ثبت نشده',
                            'pending' => 'در حال بررسی',
                            'verified' => 'تأییدشده',
                        ])->required(),
                        TextInput::make('enamad_identifier')->label('شناسه Enamad'),
                        TextInput::make('enamad_verification_url')->label('لینک بررسی اعتبار')->url()->columnSpanFull(),
                        TextInput::make('enamad_badge_image_url')->label('URL تصویر Badge')->url()->columnSpanFull(),
                        TextInput::make('enamad_alt_text')->label('Alt تصویر'),
                        Select::make('enamad_display_location')->label('محل نمایش')->options([
                            'footer' => 'فوتر',
                            'trust-page' => 'صفحه اعتماد',
                        ])->required(),
                    ])->columns(2),
                    Tabs\Tab::make('SEO')->schema([
                        TextInput::make('seo_site_name')->label('نام سایت'),
                        TextInput::make('seo_locale')->label('Locale'),
                        Textarea::make('seo_description')->label('توضیح سازمان')->rows(3)->columnSpanFull(),
                        TextInput::make('seo_instagram_url')->label('Instagram URL')->url()->columnSpanFull(),
                    ])->columns(2),
                ])->columnSpanFull()->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $this->putJsonSetting('brand', 'brand.identity', 'هویت برند', [
            'name' => $state['brand_name'] ?? '',
            'nameFa' => $state['brand_name_fa'] ?? '',
            'category' => $state['brand_category'] ?? '',
            'city' => $state['brand_city'] ?? '',
            'province' => $state['brand_province'] ?? '',
            'physicalLocation' => $state['brand_location'] ?? '',
            'physicalLocationShort' => $state['brand_location_short'] ?? '',
            'instagramHandle' => $state['brand_instagram_handle'] ?? '',
            'instagramUrl' => $state['brand_instagram_url'] ?? '',
            'slogan' => $state['brand_slogan'] ?? '',
            'storyTitle' => $state['brand_story_title'] ?? '',
            'descriptor' => $state['brand_descriptor'] ?? '',
            'shortIntroduction' => $state['brand_short_introduction'] ?? '',
        ]);

        $this->putJsonSetting('brand', 'brand.copy', 'متن‌های اصلی برند', [
            'homepageTitle' => $state['homepage_title'] ?? '',
            'homepageDescription' => $state['homepage_description'] ?? '',
            'heroEyebrow' => $state['hero_eyebrow'] ?? '',
            'heroTitle' => $state['hero_title'] ?? '',
            'heroBody' => $state['hero_body'] ?? '',
            'primaryCta' => $state['primary_cta'] ?? '',
            'secondaryCta' => $state['secondary_cta'] ?? '',
            'storeLocationLabel' => $state['store_location_label'] ?? '',
        ]);

        $existingContact = $this->jsonSetting('contact', 'contact.public');
        $this->putJsonSetting('contact', 'contact.public', 'اطلاعات تماس عمومی', [
            ...$existingContact,
            'phone' => $state['contact_phone'] ?? '',
            'whatsapp' => $state['contact_whatsapp'] ?? '',
            'email' => $state['contact_email'] ?: null,
            'instagramHandle' => $state['brand_instagram_handle'] ?? '',
            'instagramUrl' => $state['brand_instagram_url'] ?? '',
            'locationLabel' => $state['brand_location_short'] ?? '',
            'addressLine' => $state['contact_address_line'] ?: null,
            'mapUrl' => $state['contact_map_url'] ?: null,
            'openingHours' => $this->stringValues($state['contact_opening_hours'] ?? []),
            'city' => $state['brand_city'] ?? '',
            'province' => $state['brand_province'] ?? '',
        ]);

        $existingIntro = $this->jsonSetting('home', 'home.brand_intro');
        $this->putJsonSetting('home', 'home.brand_intro', 'معرفی برند در خانه', [
            ...$existingIntro,
            'enabled' => (bool) ($existingIntro['enabled'] ?? true),
            'version' => (string) ($existingIntro['version'] ?? 'v1'),
            'eyebrow' => $state['intro_eyebrow'] ?? '',
            'title' => $state['intro_title'] ?? '',
            'body' => $state['intro_body'] ?? '',
            'storyCta' => $state['intro_story_cta'] ?? '',
            'storeCta' => $state['intro_store_cta'] ?? '',
        ]);

        $this->putJsonSetting('home', 'home.presentation', 'چیدمان صفحه اصلی', [
            'heroProductSlug' => $state['hero_product_slug'] ?? '',
            'categoryOrder' => $this->stringValues($state['category_order'] ?? []),
            'sections' => $this->stringValues($state['home_sections'] ?? []),
        ]);

        $this->putJsonSetting('home', 'home.ticker', 'نوار متحرک صفحه اصلی', $this->repeaterStrings($state['ticker_items'] ?? []));
        $this->putJsonSetting('home', 'home.trust', 'کارت‌های اعتماد صفحه اصلی', $this->rows($state['trust_items'] ?? []));
        $this->putJsonSetting('home', 'home.section_copy', 'متن سکشن‌های صفحه اصلی', [
            'hero' => [
                'helperText' => $state['hero_helper_text'] ?? '',
                'categoryPrompt' => $state['hero_category_prompt'] ?? '',
                'categoryAnchorLabel' => $state['hero_category_anchor_label'] ?? '',
            ],
            'categories' => [
                'label' => $state['category_section_label'] ?? '',
                'title' => $state['category_section_title'] ?? '',
                'lede' => $state['category_section_lede'] ?? '',
                'actionLabel' => $state['category_section_action'] ?? '',
            ],
            'products' => [
                'label' => $state['product_section_label'] ?? '',
                'title' => $state['product_section_title'] ?? '',
                'lede' => $state['product_section_lede'] ?? '',
                'actionLabel' => $state['product_section_action'] ?? '',
            ],
            'instagram' => [
                'followCta' => $state['instagram_follow_cta'] ?? '',
                'lookbookCta' => $state['instagram_lookbook_cta'] ?? '',
            ],
        ]);
        $this->putJsonSetting('home', 'home.decision_support', 'راهنمای انتخاب صفحه اصلی', [
            'enabled' => (bool) ($state['decision_enabled'] ?? false),
            'label' => $state['decision_label'] ?? '',
            'title' => $state['decision_title'] ?? '',
            'lede' => $state['decision_lede'] ?? '',
            'checkLabel' => $state['decision_check_label'] ?? '',
            'checks' => $this->repeaterStrings($state['decision_checks'] ?? []),
            'links' => $this->rows($state['decision_links'] ?? []),
        ]);
        $this->putJsonSetting('home', 'home.local_store', 'سکشن فروشگاه حضوری', [
            'enabled' => (bool) ($state['local_store_enabled'] ?? false),
            'eyebrow' => $state['local_store_eyebrow'] ?? '',
            'title' => $state['local_store_title'] ?? '',
            'body' => $state['local_store_body'] ?? '',
            'imageUrl' => $state['local_store_image_url'] ?: null,
            'addressTitle' => $state['local_store_address_title'] ?? '',
            'instagramTitle' => $state['local_store_instagram_title'] ?? '',
            'contactCta' => $state['local_store_contact_cta'] ?? '',
            'instagramCta' => $state['local_store_instagram_cta'] ?? '',
            'shopCta' => $state['local_store_shop_cta'] ?? '',
        ]);
        $this->putJsonSetting('home', 'home.featured_story', 'Featured Collection Story', [
            'enabled' => (bool) ($state['featured_story_enabled'] ?? false),
            'collectionSlug' => trim((string) ($state['featured_story_collection_slug'] ?? '')),
            'eyebrow' => $state['featured_story_eyebrow'] ?? '',
            'storyPoints' => $this->repeaterStrings($state['featured_story_points'] ?? []),
            'collectionCta' => $state['featured_story_collection_cta'] ?? '',
            'lookbookCta' => $state['featured_story_lookbook_cta'] ?? '',
            'inventoryNote' => $state['featured_story_inventory_note'] ?? '',
        ]);

        $customerPaysShipping = $state['returns_customer_pays_shipping'] ?? null;
        $this->putJsonSetting('policy', 'policy.returns', 'سیاست عمومی مرجوعی', [
            'enabled' => (bool) ($state['returns_enabled'] ?? false),
            'verification' => $state['returns_verification'] ?? 'pending',
            'exchangeEnabled' => (bool) ($state['returns_exchange_enabled'] ?? false),
            'returnWindowDays' => $state['returns_window_days'] === null || $state['returns_window_days'] === '' ? null : (int) $state['returns_window_days'],
            'refundTimeLabel' => $state['returns_refund_time_label'] ?: null,
            'customerPaysReturnShipping' => $customerPaysShipping === null || $customerPaysShipping === '' ? null : (bool) (int) $customerPaysShipping,
            'quickIssueNoticeHours' => $state['returns_quick_issue_notice_hours'] === null || $state['returns_quick_issue_notice_hours'] === '' ? null : (int) $state['returns_quick_issue_notice_hours'],
        ]);
        $this->putJsonSetting('trust', 'trust.enamad', 'نماد اعتماد عمومی', [
            'enabled' => (bool) ($state['enamad_enabled'] ?? false),
            'verification' => $state['enamad_verification'] ?? 'missing',
            'identifier' => $state['enamad_identifier'] ?: null,
            'verificationUrl' => $state['enamad_verification_url'] ?: null,
            'badgeImageUrl' => $state['enamad_badge_image_url'] ?: null,
            'altText' => $state['enamad_alt_text'] ?: 'نماد اعتماد الکترونیکی LBB',
            'displayLocation' => $state['enamad_display_location'] ?? 'footer',
        ]);

        $this->putJsonSetting('seo', 'seo.defaults', 'SEO پیش‌فرض', [
            'siteName' => $state['seo_site_name'] ?? '',
            'locale' => $state['seo_locale'] ?? 'fa_IR',
            'organizationDescription' => $state['seo_description'] ?? '',
            'instagramUrl' => $state['seo_instagram_url'] ?? '',
        ]);

        Notification::make()->title('تنظیمات عمومی و Storefront ذخیره شد')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('ذخیره تنظیمات')->submit('save')];
    }

    /** @param array<mixed> $values */
    private function stringValues(array $values): array
    {
        return collect($values)
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->values()
            ->all();
    }

    /** @param array<mixed> $items */
    private function repeaterStrings(array $items): array
    {
        return collect($items)
            ->filter(fn ($value): bool => is_array($value))
            ->map(fn (array $item): string => trim((string) ($item['text'] ?? '')))
            ->filter()
            ->values()
            ->all();
    }

    /** @param array<mixed> $items */
    private function rows(array $items): array
    {
        return collect($items)->filter(fn ($value): bool => is_array($value))->values()->all();
    }

    private function jsonSetting(string $group, string $key, array $default = []): array
    {
        $setting = StoreSetting::query()->where('group', $group)->where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        $value = $setting->typedValue();

        return is_array($value) ? $value : $default;
    }

    private function putJsonSetting(string $group, string $key, string $label, array $value): void
    {
        StoreSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'label' => $label,
                'type' => 'json',
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_public' => true,
            ],
        );
    }
}
