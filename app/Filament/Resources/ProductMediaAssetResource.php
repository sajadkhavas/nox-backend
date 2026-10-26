<?php

namespace App\Filament\Resources;

use App\Enums\EvidenceState;
use App\Filament\Resources\ProductMediaAssetResource\Pages;
use App\Models\ProductMediaAsset;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductMediaAssetResource extends Resource
{
    protected static ?string $model = ProductMediaAsset::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'رسانه پوشاک';

    protected static ?string $modelLabel = 'رسانه محصول';

    protected static ?string $pluralModelLabel = 'رسانه پوشاک';

    protected static ?string $navigationGroup = 'دامنه پوشاک';

    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('product_id')
                ->label('محصول')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->live()
                ->required(),
            Forms\Components\Select::make('color_id')
                ->label('رنگ اختیاری')
                ->relationship('color', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('variant_id')
                ->label('Variant اختیاری')
                ->options(fn (Forms\Get $get): array => filled($get('product_id'))
                    ? ProductVariant::query()
                        ->where('product_id', $get('product_id'))
                        ->orderBy('sku')
                        ->pluck('sku', 'id')
                        ->all()
                    : [])
                ->searchable(),
            Forms\Components\Select::make('role')
                ->label('نقش')
                ->options([
                    'front' => 'front',
                    'back' => 'back',
                    'detail' => 'detail',
                    'lifestyle' => 'lifestyle',
                    'swatch' => 'swatch',
                ])
                ->default('detail')
                ->required(),
            SpatieMediaLibraryFileUpload::make('asset')
                ->label('فایل تصویر')
                ->collection('asset')
                ->image()
                ->required()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                ->columnSpanFull(),
            Forms\Components\TextInput::make('alt_text')->label('Alt text')->maxLength(255)->columnSpanFull(),
            Forms\Components\Select::make('verification_state')
                ->label('وضعیت تأیید')
                ->options(collect(EvidenceState::cases())->mapWithKeys(
                    fn (EvidenceState $state): array => [$state->value => $state->value],
                )->all())
                ->default(EvidenceState::Missing->value)
                ->required(),
            Forms\Components\TextInput::make('sort_order')->label('ترتیب')->numeric()->minValue(0)->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('asset')->label('تصویر')->collection('asset')->square(),
                Tables\Columns\TextColumn::make('product.name')->label('محصول')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('color.name')->label('رنگ')->placeholder('عمومی'),
                Tables\Columns\TextColumn::make('variant.sku')->label('SKU')->placeholder('—'),
                Tables\Columns\TextColumn::make('role')->label('نقش')->badge(),
                Tables\Columns\TextColumn::make('verification_state')->label('Evidence')->badge(),
                Tables\Columns\TextColumn::make('sort_order')->label('ترتیب')->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageProductMediaAssets::route('/')];
    }
}
