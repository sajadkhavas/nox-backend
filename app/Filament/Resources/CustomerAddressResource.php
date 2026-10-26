<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerAddressResource\Pages;
use App\Models\CustomerAddress;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerAddressResource extends Resource
{
    protected static ?string $model = CustomerAddress::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'آدرس‌های مشتریان';

    protected static ?string $modelLabel = 'آدرس مشتری';

    protected static ?string $pluralModelLabel = 'آدرس‌های مشتریان';

    protected static ?string $navigationGroup = 'فروشگاه LBB';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('customer.full_name')->label('مشتری')->disabled(),
            Forms\Components\TextInput::make('title')->label('عنوان')->disabled(),
            Forms\Components\TextInput::make('recipient_name')->label('گیرنده')->disabled(),
            Forms\Components\TextInput::make('mobile')->label('موبایل')->disabled(),
            Forms\Components\TextInput::make('province')->label('استان')->disabled(),
            Forms\Components\TextInput::make('city')->label('شهر')->disabled(),
            Forms\Components\TextInput::make('postal_code')->label('کد پستی')->disabled(),
            Forms\Components\Toggle::make('is_default')->label('پیش‌فرض')->disabled(),
            Forms\Components\Toggle::make('is_active')->label('فعال'),
            Forms\Components\Textarea::make('address_line')->label('آدرس')->disabled()->rows(4)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.full_name')->label('مشتری')->searchable(),
                Tables\Columns\TextColumn::make('recipient_name')->label('گیرنده')->searchable(),
                Tables\Columns\TextColumn::make('mobile')->label('موبایل')->searchable(),
                Tables\Columns\TextColumn::make('province')->label('استان')->searchable(),
                Tables\Columns\TextColumn::make('city')->label('شهر')->searchable(),
                Tables\Columns\IconColumn::make('is_default')->label('پیش‌فرض')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_default')->label('پیش‌فرض'),
                Tables\Filters\TernaryFilter::make('is_active')->label('فعال'),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('مشاهده')])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCustomerAddresses::route('/')];
    }
}
