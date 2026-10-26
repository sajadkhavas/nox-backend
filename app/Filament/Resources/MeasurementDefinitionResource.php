<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeasurementDefinitionResource\Pages;
use App\Models\MeasurementDefinition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MeasurementDefinitionResource extends Resource
{
    protected static ?string $model = MeasurementDefinition::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'تعریف اندازه‌ها';

    protected static ?string $modelLabel = 'نوع اندازه';

    protected static ?string $pluralModelLabel = 'تعریف اندازه‌ها';

    protected static ?string $navigationGroup = 'دامنه پوشاک';

    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')
                ->label('Code')
                ->required()
                ->maxLength(64)
                ->unique(ignoreRecord: true)
                ->helperText('مثلاً نوع اندازه را تعریف کنید؛ هیچ Dimension فروشگاهی Seed نمی‌شود.'),
            Forms\Components\TextInput::make('label')->label('عنوان')->required()->maxLength(120),
            Forms\Components\TextInput::make('sort_order')->label('ترتیب')->numeric()->minValue(0)->default(0),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Code')->searchable(),
                Tables\Columns\TextColumn::make('label')->label('عنوان')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageMeasurementDefinitions::route('/')];
    }
}
