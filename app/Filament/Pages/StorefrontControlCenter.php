<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Collection as ApparelCollection;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\StorefrontMediaOptions;
use App\Support\StorefrontPresentationDefaults;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StorefrontControlCenter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'کنترل ویترین';

    protected static ?string $title = 'کنترل کامل ویترین و محتوای عمومی';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.site-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $defaults = StorefrontPresentationDefaults::settings();
        $home = $this->objectSetting('home', 'home.presentation', $defaults['home']['home.presentation']);
        $sectionCopy = $this->objectSetting('home', 'home.section_copy', $defaults['home']['home.section_copy']);
        $localStore = $this->objectSetting('home', 'home.local_store', $defaults['home']['home.local_store']);
        $featuredStory = $this->objectSetting('home', 'home.featured_story', $defaults['home']['home.featured_story']);
        $intro = $this->objectSetting('home', 'home.brand_intro', $defaults['home']['home.brand_intro']);
        $shell = $this->objectSetting('shell', 'shell.copy', $defaults['shell']['shell.copy']);
        $pages = $this->objectSetting('page', 'page.presentation', $defaults['page']['page.presentation']);
        $faqPresentation = $this->objectSetting('faq', 'faq.presentation', $defaults['faq']['faq.presentation']);
        $curation = is_array($home['productCuration'] ?? null) ? $home['productCuration'] : [];

        $this->data = [
            'hero_enabled' => (bool) ($home['heroEnabled'] ?? true),
            'hero_product_slug' => (string) ($home['heroProductSlug'] ?? ''),
            'hero_image_path' => $home['heroImagePath'] ?? null,
            'hero_image_alt' => (string) ($home['heroImageAlt'] ?? ''),
            'hero_image_fit' => (string) ($home['heroImageFit'] ?? 'contain'),
            'hero_image_position' => (string) ($home['heroImagePosition'] ?? 'center center'),
            'hero_primary_cta_href' => (string) ($home['primaryCtaHref'] ?? '/shop'),
            'hero_secondary_cta_href' => (string) ($home['secondaryCtaHref'] ?? '/contact'),
            'category_order' => $this->stringRows($home['categoryOrder'] ?? [], 'slug'),
            'home_sections' => $this->stringRows($home['sections'] ?? [], 'key'),
            'product_curation_mode' => (string) ($curation['mode'] ?? 'newest'),
            'product_curation_count' => (int) ($curation['count'] ?? 4),
            'manual_products' => $this->stringRows($curation['manualProductSlugs'] ?? [], 'slug'),
            'category_action_href' => (string) (($sectionCopy['categories']['actionHref'] ?? null) ?: '/shop'),
            'product_action_href' => (string) (($sectionCopy['products']['actionHref'] ?? null) ?: '/shop'),
            'instagram_lookbook_href' => (string) (($sectionCopy['instagram']['lookbookHref'] ?? null) ?: '/lookbook'),
            'announcement_messages' => $this->arrayRows(
                $this->listSetting('announcement', 'announcement.messages', $defaults['announcement']['announcement.messages'])
            ),
            'navigation_shop' => $this->arrayRows(
                $this->listSetting('navigation', 'navigation.shop', $defaults['navigation']['navigation.shop'])
            ),
            'navigation_editorial' => $this->arrayRows(
                $this->listSetting('navigation', 'navigation.editorial', $defaults['navigation']['navigation.editorial'])
            ),
            'navigation_service' => $this->arrayRows(
                $this->listSetting('navigation', 'navigation.service', $defaults['navigation']['navigation.service'])
            ),
            'navigation_brand' => $this->arrayRows(
                $this->listSetting('navigation', 'navigation.brand', $defaults['navigation']['navigation.brand'])
            ),
            'local_store_image_path' => $localStore['imagePath'] ?? null,
            'local_store_contact_href' => (string) (($localStore['contactHref'] ?? null) ?: '/contact'),
            'local_store_instagram_href' => (string) ($localStore['instagramHref'] ?? ''),
            'local_store_shop_href' => (string) (($localStore['shopHref'] ?? null) ?: '/shop'),
            'featured_story_collection_slug' => (string) ($featuredStory['collectionSlug'] ?? ''),
            'intro_enabled' => (bool) ($intro['enabled'] ?? true),
            'intro_version' => (string) ($intro['version'] ?? 'v1'),
            'shop_menu_label' => (string) ($shell['shopMenuLabel'] ?? 'فروشگاه'),
            'footer_group_shop' => (string) ($shell['footerGroups']['shop'] ?? 'خرید'),
            'footer_group_editorial' => (string) ($shell['footerGroups']['editorial'] ?? 'کالکشن و محتوا'),
            'footer_group_service' => (string) ($shell['footerGroups']['service'] ?? 'پشتیبانی'),
            'footer_group_personal' => (string) ($shell['footerGroups']['personal'] ?? 'شخصی'),
            'footer_group_brand' => (string) ($shell['footerGroups']['brand'] ?? 'برند'),
            'utility_links' => $this->arrayRows($shell['utilityLinks'] ?? []),
            'page_presentations' => $this->pageRows($pages),
            'faq_categories' => $this->faqRows($faqPresentation),
        ];

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('کنترل ویترین')->tabs([
                    Tabs\Tab::make('Hero و صفحه اصلی')->schema([
                        Toggle::make('hero_enabled')
                            ->label('نمایش Hero')
                            ->helperText('خاموش‌کردن Hero فقط نمایش را متوقف می‌کند و داده محصول را تغییر نمی‌دهد.'),
                        Select::make('hero_product_slug')
                            ->label('محصول Hero')
                            ->options(fn (): array => $this->productOptions())
                            ->searchable()
                            ->preload()
                            ->placeholder('بدون محصول منتخب')
                            ->helperText('فقط محصول واقعی منتشرشده را انتخاب کنید؛ نیازی به واردکردن Slug نیست.'),
                        Select::make('hero_image_path')
                            ->label('تصویر مستقل Hero از کتابخانه')
                            ->options(fn (): array => StorefrontMediaOptions::paths($this->data['hero_image_path'] ?? null))
                            ->searchable()
                            ->helperText('اگر خالی باشد، تصویر محصول Hero به‌عنوان fallback استفاده می‌شود.')
                            ->columnSpanFull(),
                        TextInput::make('hero_image_alt')->label('Alt تصویر Hero')->maxLength(220)->columnSpanFull(),
                        Select::make('hero_image_fit')->label('نحوه نمایش تصویر')->options([
                            'contain' => 'کامل داخل کادر (Contain)',
                            'cover' => 'پرکردن کادر (Cover)',
                        ])->required(),
                        Select::make('hero_image_position')->label('نقطه تمرکز تصویر')->options([
                            'center center' => 'مرکز',
                            'center top' => 'بالا',
                            'center bottom' => 'پایین',
                            'right center' => 'راست',
                            'left center' => 'چپ',
                        ])->required(),
                        TextInput::make('hero_primary_cta_href')->label('مقصد CTA اصلی')->helperText('مسیر داخلی مثل /shop یا HTTPS')->maxLength(500),
                        TextInput::make('hero_secondary_cta_href')->label('مقصد CTA دوم')->helperText('مسیر داخلی مثل /contact یا HTTPS')->maxLength(500),
                        Repeater::make('home_sections')
                            ->label('ترتیب سکشن‌های صفحه اصلی')
                            ->schema([
                                Select::make('key')
                                    ->label('سکشن')
                                    ->options(self::homeSectionOptions())
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            ])
                            ->minItems(1)
                            ->maxItems(8)
                            ->reorderable()
                            ->columnSpanFull(),
                        Repeater::make('category_order')
                            ->label('ترتیب دسته‌های Home/Hero')
                            ->schema([
                                Select::make('slug')
                                    ->label('دسته')
                                    ->options(fn (): array => $this->categoryOptions())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            ])
                            ->maxItems(12)
                            ->reorderable()
                            ->columnSpanFull(),
                        Select::make('product_curation_mode')->label('منبع محصولات منتخب Home')->options([
                            'newest' => 'جدیدترین محصولات',
                            'featured' => 'محصولات علامت‌خورده به‌عنوان ویژه',
                            'manual' => 'انتخاب دستی',
                        ])->required(),
                        TextInput::make('product_curation_count')->label('تعداد کارت محصول Home')->numeric()->minValue(1)->maxValue(12)->required(),
                        Repeater::make('manual_products')
                            ->label('محصولات انتخاب دستی')
                            ->schema([
                                Select::make('slug')
                                    ->label('محصول')
                                    ->options(fn (): array => $this->productOptions())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            ])
                            ->maxItems(12)
                            ->reorderable()
                            ->helperText('فقط وقتی حالت انتخاب دستی فعال است استفاده می‌شود.')
                            ->columnSpanFull(),
                        TextInput::make('category_action_href')->label('مقصد CTA سکشن دسته‌ها')->maxLength(500),
                        TextInput::make('product_action_href')->label('مقصد CTA سکشن محصولات')->maxLength(500),
                        TextInput::make('instagram_lookbook_href')->label('مقصد CTA لوک‌بوک در سکشن اینستاگرام')->maxLength(500),
                    ])->columns(2),

                    Tabs\Tab::make('اطلاعیه و Navigation')->schema([
                        Repeater::make('announcement_messages')
                            ->label('نوار اطلاعیه بالای سایت')
                            ->schema([
                                TextInput::make('text')->label('متن')->required()->maxLength(180),
                                TextInput::make('href')->label('مقصد')->required()->maxLength(500),
                                Toggle::make('enabled')->label('نمایش')->default(true),
                            ])
                            ->columns(2)
                            ->maxItems(20)
                            ->reorderable()
                            ->columnSpanFull(),
                        ...$this->navigationRepeaters(),
                    ]),

                    Tabs\Tab::make('رسانه و Story')->schema([
                        Select::make('local_store_image_path')
                            ->label('تصویر فروشگاه حضوری از کتابخانه')
                            ->options(fn (): array => StorefrontMediaOptions::paths($this->data['local_store_image_path'] ?? null))
                            ->searchable()
                            ->columnSpanFull(),
                        TextInput::make('local_store_contact_href')->label('مقصد CTA تماس فروشگاه')->maxLength(500),
                        TextInput::make('local_store_instagram_href')->label('مقصد CTA اینستاگرام (اختیاری)')->helperText('خالی = Instagram عمومی برند')->maxLength(500),
                        TextInput::make('local_store_shop_href')->label('مقصد CTA فروشگاه')->maxLength(500),
                        Select::make('featured_story_collection_slug')
                            ->label('کالکشن Featured Story')
                            ->options(fn (): array => $this->collectionOptions())
                            ->searchable()
                            ->preload()
                            ->placeholder('بدون کالکشن منتخب')
                            ->helperText('کالکشن منتشرشده را انتخاب کنید؛ Slug دستی لازم نیست.'),
                    ])->columns(2),

                    Tabs\Tab::make('Brand Intro')->schema([
                        Toggle::make('intro_enabled')->label('فعال بودن معرفی اولین بازدید'),
                        TextInput::make('intro_version')
                            ->label('نسخه نمایش')
                            ->required()
                            ->maxLength(80)
                            ->helperText('با تغییر نسخه، Intro برای مرورگرهایی که نسخه قبلی را دیده‌اند دوباره نمایش داده می‌شود.'),
                    ])->columns(2),

                    Tabs\Tab::make('فوتر و Shell')->schema([
                        TextInput::make('shop_menu_label')->label('عنوان منوی فروشگاه')->required()->maxLength(80),
                        TextInput::make('footer_group_shop')->label('عنوان ستون خرید')->required()->maxLength(80),
                        TextInput::make('footer_group_editorial')->label('عنوان ستون محتوا')->required()->maxLength(80),
                        TextInput::make('footer_group_service')->label('عنوان ستون پشتیبانی')->required()->maxLength(80),
                        TextInput::make('footer_group_personal')->label('عنوان ستون شخصی')->required()->maxLength(80),
                        TextInput::make('footer_group_brand')->label('عنوان ستون برند')->required()->maxLength(80),
                        Repeater::make('utility_links')
                            ->label('لینک‌های پایین فوتر')
                            ->schema([
                                TextInput::make('label')->label('عنوان')->required()->maxLength(100),
                                TextInput::make('href')->label('مقصد')->required()->maxLength(500),
                                Toggle::make('enabled')->label('نمایش')->default(true),
                            ])
                            ->columns(2)
                            ->maxItems(12)
                            ->reorderable()
                            ->columnSpanFull(),
                    ])->columns(2),

                    Tabs\Tab::make('صفحات و SEO')->schema([
                        Repeater::make('page_presentations')
                            ->label('Presentation صفحات اصلی')
                            ->schema([
                                Select::make('page')
                                    ->label('صفحه')
                                    ->options(self::pageOptions())
                                    ->required()
                                    ->distinct(),
                                TextInput::make('metaTitle')->label('Meta title')->required()->maxLength(220)->columnSpanFull(),
                                Textarea::make('metaDescription')->label('Meta description')->rows(2)->required()->maxLength(500)->columnSpanFull(),
                                TextInput::make('eyebrow')->label('Eyebrow')->maxLength(140),
                                TextInput::make('title')->label('عنوان نمایشی')->required()->maxLength(220),
                                Textarea::make('lede')->label('متن معرفی')->rows(3)->columnSpanFull(),
                                TextInput::make('sectionLabel')->label('Label سکشن تکمیلی')->maxLength(140),
                                TextInput::make('sectionTitle')->label('عنوان سکشن تکمیلی')->maxLength(220),
                                TextInput::make('primaryCtaLabel')->label('CTA اصلی')->maxLength(120),
                                TextInput::make('primaryCtaHref')->label('مقصد CTA اصلی')->maxLength(500),
                                TextInput::make('secondaryCtaLabel')->label('CTA دوم')->maxLength(120),
                                TextInput::make('secondaryCtaHref')->label('مقصد CTA دوم')->maxLength(500),
                                Select::make('socialImagePath')
                                    ->label('تصویر Social/OG از کتابخانه')
                                    ->options(fn (): array => StorefrontMediaOptions::paths())
                                    ->searchable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->minItems(5)
                            ->maxItems(5)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),

                    Tabs\Tab::make('FAQ Categories')->schema([
                        Repeater::make('faq_categories')
                            ->label('نام نمایشی گروه‌های FAQ')
                            ->schema([
                                TextInput::make('key')->label('کلید')->required()->maxLength(100),
                                TextInput::make('label')->label('Label')->required()->maxLength(120),
                                TextInput::make('title')->label('عنوان فارسی')->required()->maxLength(180),
                            ])
                            ->columns(3)
                            ->maxItems(30)
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
                ])->columnSpanFull()->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        DB::transaction(function () use ($state): void {
            $home = $this->jsonSetting('home', 'home.presentation');
            $home['heroEnabled'] = (bool) ($state['hero_enabled'] ?? true);
            $home['heroProductSlug'] = trim((string) ($state['hero_product_slug'] ?? ''));
            $home['heroImagePath'] = $this->nullableString($state['hero_image_path'] ?? null);
            $home['heroImageAlt'] = trim((string) ($state['hero_image_alt'] ?? ''));
            $home['heroImageFit'] = in_array(($state['hero_image_fit'] ?? 'contain'), ['contain', 'cover'], true)
                ? $state['hero_image_fit']
                : 'contain';
            $home['heroImagePosition'] = in_array(($state['hero_image_position'] ?? 'center center'), [
                'center center', 'center top', 'center bottom', 'right center', 'left center',
            ], true) ? $state['hero_image_position'] : 'center center';
            $home['primaryCtaHref'] = $this->publicHref($state['hero_primary_cta_href'] ?? '/shop', '/shop');
            $home['secondaryCtaHref'] = $this->publicHref($state['hero_secondary_cta_href'] ?? '/contact', '/contact');
            $home['categoryOrder'] = $this->rowStrings($state['category_order'] ?? [], 'slug');
            $home['sections'] = $this->allowedSections($this->rowStrings($state['home_sections'] ?? [], 'key'));
            $home['productCuration'] = [
                'mode' => in_array(($state['product_curation_mode'] ?? 'newest'), ['newest', 'featured', 'manual'], true)
                    ? $state['product_curation_mode']
                    : 'newest',
                'count' => max(1, min(12, (int) ($state['product_curation_count'] ?? 4))),
                'manualProductSlugs' => $this->rowStrings($state['manual_products'] ?? [], 'slug'),
            ];
            $this->putJsonSetting('home', 'home.presentation', 'چیدمان و انتخاب‌های صفحه اصلی', $home);

            $sectionCopy = $this->jsonSetting('home', 'home.section_copy');
            $sectionCopy['categories']['actionHref'] = $this->publicHref($state['category_action_href'] ?? '/shop', '/shop');
            $sectionCopy['products']['actionHref'] = $this->publicHref($state['product_action_href'] ?? '/shop', '/shop');
            $sectionCopy['instagram']['lookbookHref'] = $this->publicHref($state['instagram_lookbook_href'] ?? '/lookbook', '/lookbook');
            $this->putJsonSetting('home', 'home.section_copy', 'متن و لینک سکشن‌های صفحه اصلی', $sectionCopy);

            $this->putJsonSetting(
                'announcement',
                'announcement.messages',
                'نوار اطلاعیه عمومی',
                $this->linkRows($state['announcement_messages'] ?? [], requireText: true),
            );

            foreach (['shop', 'editorial', 'service', 'brand'] as $group) {
                $this->putJsonSetting(
                    'navigation',
                    'navigation.'.$group,
                    'Navigation '.$group,
                    $this->navigationRows($state['navigation_'.$group] ?? []),
                );
            }

            $localStore = $this->jsonSetting('home', 'home.local_store');
            $localStore['imagePath'] = $this->nullableString($state['local_store_image_path'] ?? null);
            $localStore['contactHref'] = $this->publicHref($state['local_store_contact_href'] ?? '/contact', '/contact');
            $localStore['instagramHref'] = $this->publicHref($state['local_store_instagram_href'] ?? null, null, true);
            $localStore['shopHref'] = $this->publicHref($state['local_store_shop_href'] ?? '/shop', '/shop');
            $this->putJsonSetting('home', 'home.local_store', 'سکشن فروشگاه حضوری', $localStore);

            $featuredStory = $this->jsonSetting('home', 'home.featured_story');
            $featuredStory['collectionSlug'] = trim((string) ($state['featured_story_collection_slug'] ?? ''));
            $this->putJsonSetting('home', 'home.featured_story', 'Featured Collection Story', $featuredStory);

            $intro = $this->jsonSetting('home', 'home.brand_intro');
            $intro['enabled'] = (bool) ($state['intro_enabled'] ?? true);
            $intro['version'] = trim((string) ($state['intro_version'] ?? 'v1')) ?: 'v1';
            $this->putJsonSetting('home', 'home.brand_intro', 'معرفی اولین بازدید', $intro);

            $shell = $this->jsonSetting('shell', 'shell.copy');
            $shell['shopMenuLabel'] = trim((string) ($state['shop_menu_label'] ?? 'فروشگاه')) ?: 'فروشگاه';
            $shell['footerGroups'] = [
                'shop' => trim((string) ($state['footer_group_shop'] ?? 'خرید')) ?: 'خرید',
                'editorial' => trim((string) ($state['footer_group_editorial'] ?? 'کالکشن و محتوا')) ?: 'کالکشن و محتوا',
                'service' => trim((string) ($state['footer_group_service'] ?? 'پشتیبانی')) ?: 'پشتیبانی',
                'personal' => trim((string) ($state['footer_group_personal'] ?? 'شخصی')) ?: 'شخصی',
                'brand' => trim((string) ($state['footer_group_brand'] ?? 'برند')) ?: 'برند',
            ];
            $shell['utilityLinks'] = $this->linkRows($state['utility_links'] ?? [], requireText: false);
            $this->putJsonSetting('shell', 'shell.copy', 'متن و لینک‌های پوسته سایت', $shell);

            $this->putJsonSetting(
                'page',
                'page.presentation',
                'Presentation صفحات اصلی',
                $this->pageMap($state['page_presentations'] ?? []),
            );
            $this->putJsonSetting(
                'faq',
                'faq.presentation',
                'نام نمایشی دسته‌های FAQ',
                $this->faqMap($state['faq_categories'] ?? []),
            );
        });

        Notification::make()
            ->title('کنترل‌های ویترین با موفقیت ذخیره شد')
            ->body('هیچ تنظیم Checkout/Payment توسط این صفحه تغییر نکرد.')
            ->success()
            ->send();

        $this->mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('show_brand_intro_again')
                ->label('نمایش دوباره Brand Intro')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalDescription('نسخه Intro تغییر می‌کند تا برای بازدیدکنندگان دوباره نمایش داده شود. سایر متن‌ها بدون تغییر می‌مانند.')
                ->action(function (): void {
                    DB::transaction(function (): void {
                        $defaults = StorefrontPresentationDefaults::settings()['home']['home.brand_intro'];
                        $intro = $this->objectSetting('home', 'home.brand_intro', $defaults);
                        $intro['enabled'] = true;
                        $intro['version'] = 'v'.now()->format('YmdHis');
                        $this->putJsonSetting('home', 'home.brand_intro', 'معرفی اولین بازدید', $intro);
                    });

                    Notification::make()->title('Brand Intro برای نسخه جدید آماده شد')->success()->send();
                    $this->mount();
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('ذخیره کنترل‌های ویترین')->submit('save')];
    }

    /** @return array<int, Repeater> */
    private function navigationRepeaters(): array
    {
        $labels = [
            'shop' => 'منوی خرید',
            'editorial' => 'منوی محتوا و کالکشن',
            'service' => 'منوی خدمات',
            'brand' => 'منوی برند',
        ];

        return collect($labels)->map(
            fn (string $label, string $key): Repeater => Repeater::make('navigation_'.$key)
                ->label($label)
                ->schema([
                    TextInput::make('label')->label('عنوان')->required()->maxLength(120),
                    TextInput::make('latin')->label('عنوان لاتین')->maxLength(100),
                    Textarea::make('description')->label('توضیح')->rows(2)->columnSpanFull(),
                    TextInput::make('href')->label('مقصد')->required()->maxLength(500),
                    Toggle::make('enabled')->label('نمایش')->default(true),
                ])
                ->columns(2)
                ->maxItems(30)
                ->reorderable()
                ->columnSpanFull(),
        )->values()->all();
    }

    /** @return array<string, string> */
    private function productOptions(): array
    {
        return Product::query()
            ->published()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();
    }

    /** @return array<string, string> */
    private function categoryOptions(): array
    {
        return Category::query()
            ->published()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();
    }

    /** @return array<string, string> */
    private function collectionOptions(): array
    {
        return ApparelCollection::query()
            ->published()
            ->ordered()
            ->pluck('name', 'slug')
            ->all();
    }

    /** @return array<string, string> */
    private static function homeSectionOptions(): array
    {
        return [
            'ticker' => 'نوار متحرک',
            'trust' => 'اعتماد و راهنما',
            'categories' => 'دسته‌ها',
            'products' => 'محصولات منتخب',
            'drop_story' => 'Featured Story',
            'decision_support' => 'راهنمای انتخاب',
            'local_store' => 'فروشگاه حضوری',
            'instagram' => 'لوک‌بوک/اینستاگرام',
        ];
    }

    /** @return array<string, string> */
    private static function pageOptions(): array
    {
        return [
            'shop' => 'فروشگاه',
            'collections' => 'کالکشن‌ها',
            'lookbook' => 'لوک‌بوک',
            'journal' => 'ژورنال',
            'faq' => 'سؤالات متداول',
        ];
    }

    /** @param array<mixed> $values @return list<array<string, string>> */
    private function stringRows(array $values, string $key): array
    {
        return collect($values)
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): array => [$key => trim($value)])
            ->values()
            ->all();
    }

    /** @param mixed $value @return list<array<string, mixed>> */
    private function arrayRows(mixed $value): array
    {
        return is_array($value)
            ? collect($value)->filter(fn ($value): bool => is_array($value))->values()->all()
            : [];
    }

    /** @param array<string, mixed> $pages @return list<array<string, mixed>> */
    private function pageRows(array $pages): array
    {
        $defaults = StorefrontPresentationDefaults::settings()['page']['page.presentation'];

        return collect(self::pageOptions())
            ->map(function (string $_label, string $key) use ($pages, $defaults): array {
                $page = array_replace_recursive($defaults[$key], is_array($pages[$key] ?? null) ? $pages[$key] : []);

                return ['page' => $key, ...$page];
            })
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $presentation @return list<array<string, string>> */
    private function faqRows(array $presentation): array
    {
        return collect($presentation)
            ->filter(fn ($value): bool => is_array($value))
            ->map(fn (array $item, string $key): array => [
                'key' => $key,
                'label' => (string) ($item['label'] ?? strtoupper($key)),
                'title' => (string) ($item['title'] ?? $key),
            ])
            ->values()
            ->all();
    }

    /** @param array<mixed> $rows @return list<string> */
    private function rowStrings(array $rows, string $key): array
    {
        return collect($rows)
            ->filter(fn ($value): bool => is_array($value))
            ->map(fn (array $row): string => trim((string) ($row[$key] ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @param list<string> $sections @return list<string> */
    private function allowedSections(array $sections): array
    {
        $allowed = array_keys(self::homeSectionOptions());

        return array_values(array_filter($sections, fn (string $section): bool => in_array($section, $allowed, true)));
    }

    /** @param array<mixed> $rows @return list<array<string, mixed>> */
    private function navigationRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($value): bool => is_array($value))
            ->map(fn (array $row): array => [
                'label' => trim((string) ($row['label'] ?? '')),
                'latin' => trim((string) ($row['latin'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
                'href' => $this->publicHref($row['href'] ?? null, null),
                'enabled' => (bool) ($row['enabled'] ?? true),
            ])
            ->filter(fn (array $row): bool => $row['label'] !== '' && is_string($row['href']) && $row['href'] !== '')
            ->values()
            ->all();
    }

    /** @param array<mixed> $rows @return list<array<string, mixed>> */
    private function linkRows(array $rows, bool $requireText): array
    {
        return collect($rows)
            ->filter(fn ($value): bool => is_array($value))
            ->map(function (array $row) use ($requireText): array {
                $labelKey = $requireText ? 'text' : 'label';

                return [
                    $labelKey => trim((string) ($row[$labelKey] ?? '')),
                    'href' => $this->publicHref($row['href'] ?? null, null),
                    'enabled' => (bool) ($row['enabled'] ?? true),
                ];
            })
            ->filter(function (array $row) use ($requireText): bool {
                $labelKey = $requireText ? 'text' : 'label';

                return $row[$labelKey] !== '' && is_string($row['href']) && $row['href'] !== '';
            })
            ->values()
            ->all();
    }

    /** @param array<mixed> $rows @return array<string, array<string, mixed>> */
    private function pageMap(array $rows): array
    {
        $defaults = StorefrontPresentationDefaults::settings()['page']['page.presentation'];
        $result = $defaults;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $page = (string) ($row['page'] ?? '');
            if (! array_key_exists($page, self::pageOptions())) {
                continue;
            }

            $result[$page] = [
                'metaTitle' => trim((string) ($row['metaTitle'] ?? $defaults[$page]['metaTitle'])),
                'metaDescription' => trim((string) ($row['metaDescription'] ?? $defaults[$page]['metaDescription'])),
                'eyebrow' => trim((string) ($row['eyebrow'] ?? '')),
                'title' => trim((string) ($row['title'] ?? $defaults[$page]['title'])),
                'lede' => trim((string) ($row['lede'] ?? '')),
                'sectionLabel' => trim((string) ($row['sectionLabel'] ?? '')),
                'sectionTitle' => trim((string) ($row['sectionTitle'] ?? '')),
                'primaryCtaLabel' => trim((string) ($row['primaryCtaLabel'] ?? '')),
                'primaryCtaHref' => $this->publicHref($row['primaryCtaHref'] ?? null, null, true),
                'secondaryCtaLabel' => trim((string) ($row['secondaryCtaLabel'] ?? '')),
                'secondaryCtaHref' => $this->publicHref($row['secondaryCtaHref'] ?? null, null, true),
                'socialImagePath' => $this->nullableString($row['socialImagePath'] ?? null),
                'socialImageUrl' => null,
            ];
        }

        return $result;
    }

    /** @param array<mixed> $rows @return array<string, array<string, string>> */
    private function faqMap(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = trim((string) ($row['key'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            $title = trim((string) ($row['title'] ?? ''));
            if ($key === '' || $label === '' || $title === '') {
                continue;
            }

            $result[$key] = ['label' => $label, 'title' => $title];
        }

        return $result;
    }

    private function nullableString(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function publicHref(mixed $value, ?string $fallback, bool $nullable = false): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return $nullable ? null : $fallback;
        }

        $href = trim($value);
        $isInternal = str_starts_with($href, '/') && ! str_starts_with($href, '//');
        $isHttps = filter_var($href, FILTER_VALIDATE_URL) !== false
            && parse_url($href, PHP_URL_SCHEME) === 'https';

        if (! $isInternal && ! $isHttps) {
            throw ValidationException::withMessages([
                'data' => 'لینک عمومی نامعتبر است. فقط مسیر داخلی با / یا آدرس HTTPS مجاز است: '.$href,
            ]);
        }

        return $href;
    }

    /** @return array<string, mixed> */
    private function jsonSetting(string $group, string $key): array
    {
        $setting = StoreSetting::query()->where('group', $group)->where('key', $key)->first();
        if (! $setting) {
            return [];
        }

        $value = $setting->typedValue();

        return is_array($value) ? $value : [];
    }

    /** @param array<string, mixed> $default @return array<string, mixed> */
    private function objectSetting(string $group, string $key, array $default): array
    {
        return $this->mergeObjectDefaults($default, $this->jsonSetting($group, $key));
    }

    /** @param list<mixed> $default @return list<mixed> */
    private function listSetting(string $group, string $key, array $default): array
    {
        $value = $this->jsonSetting($group, $key);

        return array_is_list($value) ? $value : $default;
    }

    /** @param array<string, mixed> $defaults @param array<string, mixed> $current @return array<string, mixed> */
    private function mergeObjectDefaults(array $defaults, array $current): array
    {
        foreach ($defaults as $key => $default) {
            if (! array_key_exists($key, $current)) {
                $current[$key] = $default;
            } elseif (is_array($default) && is_array($current[$key]) && ! array_is_list($default)) {
                $current[$key] = $this->mergeObjectDefaults($default, $current[$key]);
            }
        }

        return $current;
    }

    /** @param array<string, mixed> $value */
    private function putJsonSetting(string $group, string $key, string $label, array $value): void
    {
        StoreSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'label' => $label,
                'type' => 'json',
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'is_public' => true,
            ],
        );
    }
}
