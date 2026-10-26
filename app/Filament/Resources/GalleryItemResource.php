<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GalleryItemResource\Pages;
use App\Models\GalleryItem;
use App\Rules\PublicStorefrontHref;
use App\Support\StorefrontMediaOptions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GalleryItemResource extends Resource
{
    protected static ?string $model = GalleryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'گالری فروشگاه';

    protected static ?string $modelLabel = 'تصویر گالری';

    protected static ?string $pluralModelLabel = 'گالری فروشگاه';

    protected static ?string $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('عنوان')->required()->maxLength(220),
            Forms\Components\TextInput::make('sort_order')->label('ترتیب')->numeric()->default(0)->required(),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
            Forms\Components\Select::make('image_path')->label('تصویر از کتابخانه')
                ->options(fn (?GalleryItem $record): array => StorefrontMediaOptions::paths($record?->image_path))
                ->searchable()
                ->rules(['required_without:image_url'])
                ->validationMessages(['required_without' => 'یک تصویر از کتابخانه انتخاب کنید یا نشانی تصویر را وارد کنید.'])
                ->columnSpanFull(),
            Forms\Components\TextInput::make('image_url')
                ->label('URL تصویر قدیمی/خارجی (اختیاری)')
                ->url()
                ->rules(['required_without:image_path'])
                ->helperText('اگر فایل آپلود شده باشد، همان فایل اولویت دارد.')
                ->nullable()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('link_url')
                ->label('لینک مقصد')
                ->rules([new PublicStorefrontHref])
                ->helperText('مسیر داخلی مثل /collections/... یا URL امن HTTPS')
                ->nullable()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('caption')->label('توضیح')->rows(3)->columnSpanFull(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')->label('تصویر')->disk('public')->square(),
                Tables\Columns\TextColumn::make('title')->label('عنوان')->searchable(),
                Tables\Columns\TextColumn::make('sort_order')->label('ترتیب')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('آخرین تغییر')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([Tables\Filters\TernaryFilter::make('is_active')->label('فعال')])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageGalleryItems::route('/')];
    }
}
