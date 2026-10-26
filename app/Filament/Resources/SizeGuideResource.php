<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SizeGuideResource\Pages;
use App\Models\SizeGuide;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SizeGuideResource extends Resource
{
    protected static ?string $model = SizeGuide::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'راهنمای سایز';

    protected static ?string $modelLabel = 'راهنمای سایز';

    protected static ?string $pluralModelLabel = 'راهنماهای سایز';

    protected static ?string $navigationGroup = 'دامنه پوشاک';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('راهنما')->schema([
                Forms\Components\TextInput::make('name')->label('نام')->required()->maxLength(160),
                Forms\Components\Select::make('unit')
                    ->label('واحد استاندارد')
                    ->options(['cm' => 'cm', 'in' => 'in'])
                    ->default('cm')
                    ->required(),
                Forms\Components\Textarea::make('description')->label('توضیحات')->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
            ])->columns(2),
            Forms\Components\Section::make('اندازه‌های واقعی لباس')->schema([
                Forms\Components\Repeater::make('measurements')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('size_id')
                            ->label('سایز')
                            ->relationship('size', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('measurement_definition_id')
                            ->label('نوع اندازه')
                            ->relationship('definition', 'label')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('value')
                            ->label('مقدار')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Forms\Components\TextInput::make('notes')->label('یادداشت')->maxLength(255),
                        Forms\Components\TextInput::make('sort_order')->label('ترتیب')->numeric()->minValue(0)->default(0),
                    ])
                    ->columns(5)
                    ->columnSpanFull()
                    ->defaultItems(0)
                    ->reorderableWithButtons(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('unit')->label('واحد')->badge(),
                Tables\Columns\TextColumn::make('measurements_count')->label('اندازه‌ها')->counts('measurements'),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSizeGuides::route('/')];
    }
}
