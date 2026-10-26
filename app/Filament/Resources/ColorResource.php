<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ColorResource\Pages;
use App\Models\Color;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ColorResource extends Resource
{
    protected static ?string $model = Color::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'رنگ‌ها';

    protected static ?string $modelLabel = 'رنگ';

    protected static ?string $pluralModelLabel = 'رنگ‌ها';

    protected static ?string $navigationGroup = 'دامنه پوشاک';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('نام رنگ')->required()->maxLength(120),
            Forms\Components\TextInput::make('code')
                ->label('کد پایدار رنگ')
                ->required()
                ->maxLength(40)
                ->unique(ignoreRecord: true)
                ->helperText('نام نمایشی و Code دو مفهوم مستقل هستند.'),
            Forms\Components\TextInput::make('slug')->label('Slug')->maxLength(140),
            Forms\Components\ColorPicker::make('hex')
                ->label('HEX اختیاری')
                ->nullable()
                ->helperText('برای رنگ‌های پارچه‌ای/چندتایی می‌تواند خالی بماند.'),
            Forms\Components\TextInput::make('sort_order')->label('ترتیب')->numeric()->minValue(0)->default(0),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('hex')->label('رنگ'),
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->label('Code')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('ترتیب')->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageColors::route('/')];
    }
}
