<?php

namespace App\Filament\Resources;

use App\Enums\EvidenceState;
use App\Enums\ProductFact;
use App\Enums\PublicationStatus;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use FilamentTiptapEditor\TiptapEditor;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'محصولات';

    protected static ?string $modelLabel = 'محصول';

    protected static ?string $pluralModelLabel = 'محصولات';

    protected static ?string $navigationGroup = 'فروشگاه LBB';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('product')->tabs([
                Forms\Components\Tabs\Tab::make('هویت و انتشار')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Section::make('هویت محصول')->schema([
                            Forms\Components\TextInput::make('name')->label('نام محصول')->required()->maxLength(180),
                            Forms\Components\TextInput::make('product_code')->label('کد محصول')->required()->maxLength(80)->unique(ignoreRecord: true),
                            Forms\Components\TextInput::make('slug')->label('Slug')->maxLength(200),
                            Forms\Components\Select::make('category_id')->label('دسته‌بندی')->relationship('category', 'name')->searchable()->preload()->required(),
                            Forms\Components\Textarea::make('short_description')->label('توضیح کوتاه')->rows(3)->maxLength(320)->columnSpanFull(),
                            TiptapEditor::make('description')->label('توضیح کامل')
                                ->tools(['heading', 'bullet-list', 'ordered-list', '|', 'bold', 'italic', 'link', 'media'])
                                ->columnSpanFull(),
                        ])->columns(2),
                        Forms\Components\Section::make('انتشار')->schema([
                            Forms\Components\Select::make('publication_status')
                                ->label('Publication')
                                ->options(self::publicationOptions())
                                ->default(PublicationStatus::Draft->value)
                                ->required()
                                ->helperText('Published فقط وقتی مجاز است که Evidence لازم Verified باشد.'),
                            Forms\Components\Toggle::make('is_active')
                                ->label('فعال در API فعلی')
                                ->default(false)
                                ->helperText('Compatibility flag تا زمان cutover عمومی در BE-D.'),
                            Forms\Components\Toggle::make('is_featured')->label('ویژه')->default(false),
                            Forms\Components\TextInput::make('sort_order')->label('ترتیب نمایش')->numeric()->minValue(0)->default(0),
                        ])->columns(4),
                    ]),
                Forms\Components\Tabs\Tab::make('اطلاعات پوشاک')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Forms\Components\Select::make('size_guide_id')
                            ->label('راهنمای سایز')
                            ->relationship('sizeGuide', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('material')->label('Material')->maxLength(255),
                        Forms\Components\Textarea::make('fabric_composition')->label('Fabric composition')->rows(4)->columnSpanFull(),
                        Forms\Components\TextInput::make('fit')->label('Fit')->maxLength(120),
                        Forms\Components\Textarea::make('care_instructions')->label('Care')->rows(4)->columnSpanFull(),
                        Forms\Components\Select::make('collections')
                            ->label('کالکشن‌ها')
                            ->relationship('collections', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('drops')
                            ->label('Dropها')
                            ->relationship('drops', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Tabs\Tab::make('Variantها')
                    ->icon('heroicon-o-squares-plus')
                    ->schema([
                        Forms\Components\Placeholder::make('matrix_help')
                            ->label('Variant Matrix')
                            ->content('در صفحه ویرایش محصول از اکشن Variant Matrix استفاده کنید. Matrix فقط combinationهای جدید را می‌سازد، variantهای موجود را reset نمی‌کند و soft-deletedها را خودکار restore نمی‌کند.'),
                        Forms\Components\Repeater::make('variants')
                            ->label('Variantهای قابل فروش')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('color_id')
                                    ->label('رنگ')
                                    ->relationship('color', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('size_id')
                                    ->label('سایز')
                                    ->relationship('size', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('name')->label('نام Variant')->required()->maxLength(120),
                                Forms\Components\TextInput::make('sku')->label('SKU')->required()->maxLength(100)->unique(ignoreRecord: true),
                                Forms\Components\TextInput::make('regular_price_toman')->label('قیمت عادی (تومان)')->numeric()->required()->minValue(1),
                                Forms\Components\TextInput::make('sale_price_toman')->label('قیمت فروش (تومان)')->numeric()->minValue(1)->lt('regular_price_toman'),
                                Forms\Components\TextInput::make('stock_quantity')->label('Stock on hand')->numeric()->required()->minValue(0)->default(0),
                                Forms\Components\TextInput::make('low_stock_threshold')->label('حد هشدار')->numeric()->required()->minValue(0)->default(5),
                                Forms\Components\Toggle::make('is_default')->label('پیش‌فرض'),
                                Forms\Components\Toggle::make('is_active')->label('فعال')->default(false),
                                Forms\Components\TextInput::make('sort_order')->label('ترتیب')->numeric()->minValue(0)->default(0),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->reorderableWithButtons()
                            ->itemLabel(fn (array $state): ?string => $state['sku'] ?? $state['name'] ?? 'Variant جدید')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Tabs\Tab::make('Evidence')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Forms\Components\Placeholder::make('evidence_rule')
                            ->label('قانون')
                            ->content('وجود مقدار به معنی تأیید مقدار نیست. برای انتشار، factهای لازم باید Verified باشند.'),
                        Forms\Components\Repeater::make('evidences')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('fact_key')
                                    ->label('Fact')
                                    ->options(self::factOptions())
                                    ->required()
                                    ->distinct(),
                                Forms\Components\Select::make('state')
                                    ->label('State')
                                    ->options(self::evidenceOptions())
                                    ->default(EvidenceState::Missing->value)
                                    ->required(),
                                Forms\Components\Textarea::make('source_reference')
                                    ->label('Source reference')
                                    ->rows(2)
                                    ->columnSpan(2),
                                Forms\Components\DateTimePicker::make('reviewed_at')->label('Reviewed at'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Tabs\Tab::make('رسانه')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Forms\Components\Placeholder::make('apparel_media_note')
                            ->label('رسانه Color/Variant')
                            ->content('رسانه production پوشاک از منوی «رسانه پوشاک» مدیریت می‌شود و می‌تواند به Color یا Variant وصل شود.'),
                        SpatieMediaLibraryFileUpload::make('main_image')
                            ->label('تصویر legacy اصلی')
                            ->collection('catalog-main')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('gallery_images')
                            ->label('گالری legacy')
                            ->collection('catalog-gallery')
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(10)
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Tabs\Tab::make('مانکن 2D')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Placeholder::make('mannequin_rule')
                            ->label('قانون Asset')
                            ->content('فقط برش روبه‌رو با پس‌زمینه شفاف PNG/WebP/AVIF استفاده شود. اگر Asset یا Slot معتبر نباشد API قابلیت را Fail-closed غیرفعال می‌کند.'),
                        Forms\Components\Toggle::make('mannequin_enabled')
                            ->label('فعال‌سازی نمایش روی مانکن')
                            ->default(false),
                        Forms\Components\Select::make('mannequin_slot')
                            ->label('Slot لباس')
                            ->options(self::mannequinSlotOptions())
                            ->placeholder('انتخاب Slot'),
                        Forms\Components\Select::make('mannequin_preset')
                            ->label('Preset')
                            ->options(self::mannequinPresetOptions())
                            ->placeholder('Preset پیش‌فرض Slot')
                            ->helperText('خالی بماند تا Preset استاندارد Slot اعمال شود.'),
                        SpatieMediaLibraryFileUpload::make('mannequin_asset')
                            ->label('Asset شفاف مانکن')
                            ->collection('mannequin-front')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/webp', 'image/avif'])
                            ->maxFiles(1)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('mannequin_offset_x')
                            ->label('Offset X (%)')
                            ->numeric()
                            ->minValue(-50)
                            ->maxValue(50)
                            ->placeholder('Preset'),
                        Forms\Components\TextInput::make('mannequin_offset_y')
                            ->label('Offset Y (%)')
                            ->numeric()
                            ->minValue(-50)
                            ->maxValue(50)
                            ->placeholder('Preset'),
                        Forms\Components\TextInput::make('mannequin_scale')
                            ->label('Scale')
                            ->numeric()
                            ->minValue(0.5)
                            ->maxValue(2)
                            ->placeholder('Preset'),
                        Forms\Components\TextInput::make('mannequin_layer')
                            ->label('Layer / Z-order')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->placeholder('Preset'),
                    ])->columns(4),
                Forms\Components\Tabs\Tab::make('SEO')
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')->label('عنوان سئو')->maxLength(70),
                        Forms\Components\Textarea::make('meta_description')->label('توضیح سئو')->maxLength(180)->rows(3),
                    ])->columns(2),
            ])->persistTabInQueryString()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('main_image')->label('تصویر')->collection('catalog-main')->square(),
                Tables\Columns\TextColumn::make('name')->label('نام محصول')->searchable()->sortable()->limit(45),
                Tables\Columns\TextColumn::make('product_code')->label('کد')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('دسته')->badge()->sortable(),
                Tables\Columns\TextColumn::make('publication_status')->label('انتشار')->badge(),
                Tables\Columns\TextColumn::make('variants_count')->label('Variant')->counts('variants'),
                Tables\Columns\TextColumn::make('variants_sum_stock_quantity')->label('موجودی کل')->sum('variants', 'stock_quantity')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('API فعال')->boolean(),
                Tables\Columns\IconColumn::make('mannequin_enabled')->label('مانکن 2D')->boolean(),
                Tables\Columns\IconColumn::make('is_featured')->label('ویژه')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')->label('دسته')->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('publication_status')->label('انتشار')->options(self::publicationOptions()),
                Tables\Filters\TernaryFilter::make('is_active')->label('API فعال'),
                Tables\Filters\TernaryFilter::make('mannequin_enabled')->label('مانکن 2D'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('ویژه'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    private static function publicationOptions(): array
    {
        return collect(PublicationStatus::cases())
            ->mapWithKeys(fn (PublicationStatus $status): array => [$status->value => $status->value])
            ->all();
    }

    private static function evidenceOptions(): array
    {
        return collect(EvidenceState::cases())
            ->mapWithKeys(fn (EvidenceState $state): array => [$state->value => $state->value])
            ->all();
    }

    private static function factOptions(): array
    {
        return collect(ProductFact::cases())
            ->mapWithKeys(fn (ProductFact $fact): array => [$fact->value => $fact->value])
            ->all();
    }

    private static function mannequinSlotOptions(): array
    {
        return collect(config('mannequin.slots', []))
            ->mapWithKeys(fn (array $slot, string $key): array => [$key => (string) ($slot['label'] ?? $key)])
            ->all();
    }

    private static function mannequinPresetOptions(): array
    {
        return collect(config('mannequin.presets', []))
            ->mapWithKeys(fn (array $preset, string $key): array => [$key => (string) ($preset['label'] ?? $key)])
            ->all();
    }
}
