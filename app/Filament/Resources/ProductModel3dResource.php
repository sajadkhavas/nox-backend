<?php

namespace App\Filament\Resources;

use App\Domain\Catalog\MannequinModel3d;
use App\Filament\Resources\ProductModel3dResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductModel3dResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'مدل‌های 3D';

    protected static ?string $modelLabel = 'مدل 3D محصول';

    protected static ?string $pluralModelLabel = 'مدل‌های 3D محصولات';

    protected static ?string $navigationGroup = 'فروشگاه LBB';

    protected static ?string $slug = 'product-3d-models';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Viewer سه‌بعدی محصول')
                ->description('این قابلیت اختیاری است. برای موبایل، دستگاه ضعیف، WebGL نامناسب یا خطای مدل، نمای 2D فعلی به‌صورت خودکار fallback می‌شود.')
                ->schema([
                    Forms\Components\Placeholder::make('product_identity')
                        ->label('محصول')
                        ->content(fn (?Product $record): string => $record?->name ?? '—'),
                    Forms\Components\Placeholder::make('fallback_state')
                        ->label('پیش‌نیاز fallback 2D')
                        ->content(fn (?Product $record): string => $record?->mannequin_enabled
                            ? 'مانکن محصول فعال است؛ معتبر بودن Asset و Slot نیز در API بررسی می‌شود.'
                            : 'مانکن 2D این محصول خاموش است؛ 3D تا زمان فعال‌شدن fallback عمومی نمی‌شود.'),
                    Forms\Components\Placeholder::make('model_rule')
                        ->label('قانون مدل')
                        ->content('فقط GLB 2.0 تک‌فایلی و self-contained تا سقف 12MB. فایل‌های ناقص، مدل‌های دارای resource خارجی یا GLB نامعتبر در API به‌صورت Fail-closed مخفی می‌شوند.'),
                    SpatieMediaLibraryFileUpload::make('mannequin_model_3d')
                        ->label('مدل سه‌بعدی GLB')
                        ->collection(MannequinModel3d::COLLECTION)
                        ->acceptedFileTypes((array) config('mannequin.model3d.mime_types', [
                            'model/gltf-binary',
                            'application/gltf-buffer',
                            'application/octet-stream',
                        ]))
                        ->rules(['extensions:glb'])
                        ->maxSize((int) ceil(MannequinModel3d::maxBytes() / 1024))
                        ->maxFiles(1)
                        ->helperText('برای محصولات مهم بارگذاری شود. نبودن فایل هیچ اثری روی گالری و مانکن 2D ندارد.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('محصول')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_code')
                    ->label('کد')
                    ->searchable(),
                Tables\Columns\IconColumn::make('mannequin_enabled')
                    ->label('Fallback 2D فعال')
                    ->boolean(),
                Tables\Columns\IconColumn::make('model3d_available')
                    ->label('مدل 3D معتبر')
                    ->state(fn (Product $record): bool => MannequinModel3d::media($record) !== null)
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('mannequin_enabled')
                    ->label('Fallback 2D فعال'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('مدیریت 3D'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductModel3ds::route('/'),
            'edit' => Pages\EditProductModel3d::route('/{record}/edit'),
        ];
    }
}
