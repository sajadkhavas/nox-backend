<?php

namespace App\Filament\Resources;

use App\Enums\PublicationStatus;
use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Support\StorefrontMediaOptions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'دسته‌های فروشگاه';

    protected static ?string $modelLabel = 'دسته فروشگاه';

    protected static ?string $pluralModelLabel = 'دسته‌های فروشگاه';

    protected static ?string $navigationGroup = 'فروشگاه LBB';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('ساختار و نمایش')->schema([
                Forms\Components\Select::make('parent_id')
                    ->label('دسته والد')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('حداکثر سه سطح پشتیبانی می‌شود؛ مثال: شلوار ← جین ← بگ.'),
                Forms\Components\Toggle::make('show_in_header')
                    ->label('نمایش در هدر')
                    ->default(false),
                Forms\Components\Toggle::make('show_on_home')
                    ->label('نمایش در صفحه اصلی')
                    ->default(false),
                Forms\Components\Select::make('icon_path')->label('آیکن دسته از کتابخانه')
                    ->options(fn (?Category $record): array => StorefrontMediaOptions::paths($record?->icon_path))
                    ->searchable()->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('اطلاعات دسته')->schema([
                Forms\Components\TextInput::make('name')->label('نام دسته')->required()->maxLength(120),
                Forms\Components\TextInput::make('slug')->label('Slug')->maxLength(140),
                Forms\Components\Textarea::make('description')->label('توضیح دسته')->rows(4)->columnSpanFull(),
                Forms\Components\Select::make('image_path')->label('تصویر دسته از کتابخانه')
                    ->options(fn (?Category $record): array => StorefrontMediaOptions::paths($record?->image_path))
                    ->searchable()->columnSpanFull(),
                Forms\Components\Select::make('publication_status')
                    ->label('Publication')
                    ->options(collect(PublicationStatus::cases())->mapWithKeys(
                        fn (PublicationStatus $status): array => [$status->value => $status->value],
                    )->all())
                    ->default(PublicationStatus::Draft->value)
                    ->required(),
                Forms\Components\Toggle::make('is_active')->label('فعال در API فعلی')->default(true),
                Forms\Components\TextInput::make('sort_order')->label('ترتیب نمایش')->numeric()->minValue(0)->default(0),
            ])->columns(2),
            Forms\Components\Section::make('سئو')->schema([
                Forms\Components\TextInput::make('meta_title')->label('عنوان سئو')->maxLength(70),
                Forms\Components\Textarea::make('meta_description')->label('توضیح سئو')->maxLength(180)->rows(3),
            ])->columns(2)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon_path')->label('آیکن')->square(),
                Tables\Columns\ImageColumn::make('image_path')->label('تصویر')->square(),
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('parent.name')->label('والد')->placeholder('ریشه')->sortable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('publication_status')->label('انتشار')->badge(),
                Tables\Columns\TextColumn::make('products_count')->label('محصولات')->counts('products')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('API فعال')->boolean(),
                Tables\Columns\IconColumn::make('show_in_header')->label('هدر')->boolean(),
                Tables\Columns\IconColumn::make('show_on_home')->label('خانه')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('ترتیب')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('publication_status')->label('انتشار')->options(
                    collect(PublicationStatus::cases())->mapWithKeys(
                        fn (PublicationStatus $status): array => [$status->value => $status->value],
                    )->all(),
                ),
                Tables\Filters\TernaryFilter::make('is_active')->label('API فعال'),
                Tables\Filters\TernaryFilter::make('show_in_header')->label('نمایش در هدر'),
                Tables\Filters\TernaryFilter::make('show_on_home')->label('نمایش در خانه'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
