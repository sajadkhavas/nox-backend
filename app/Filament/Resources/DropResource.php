<?php

namespace App\Filament\Resources;

use App\Enums\PublicationStatus;
use App\Filament\Resources\DropResource\Pages;
use App\Models\Drop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DropResource extends Resource
{
    protected static ?string $model = Drop::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Dropها';

    protected static ?string $modelLabel = 'Drop';

    protected static ?string $pluralModelLabel = 'Dropها';

    protected static ?string $navigationGroup = 'دامنه پوشاک';

    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('نام')->required()->maxLength(180),
            Forms\Components\TextInput::make('slug')->label('Slug')->maxLength(200),
            Forms\Components\Textarea::make('description')->label('توضیحات')->columnSpanFull(),
            Forms\Components\Select::make('publication_status')
                ->label('وضعیت انتشار')
                ->options(collect(PublicationStatus::cases())->mapWithKeys(
                    fn (PublicationStatus $status): array => [$status->value => $status->value],
                )->all())
                ->default(PublicationStatus::Draft->value)
                ->required(),
            Forms\Components\DateTimePicker::make('starts_at')->label('شروع اختیاری'),
            Forms\Components\DateTimePicker::make('ends_at')->label('پایان اختیاری')->after('starts_at'),
            Forms\Components\Toggle::make('is_featured')->label('ویژه'),
            Forms\Components\TextInput::make('sort_order')->label('ترتیب')->numeric()->minValue(0)->default(0),
            Forms\Components\Select::make('products')
                ->label('محصولات')
                ->relationship('products', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('meta_title')->label('Meta title')->maxLength(70),
            Forms\Components\Textarea::make('meta_description')->label('Meta description')->maxLength(180),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('publication_status')->label('انتشار')->badge(),
                Tables\Columns\TextColumn::make('starts_at')->label('شروع')->dateTime()->placeholder('—'),
                Tables\Columns\TextColumn::make('ends_at')->label('پایان')->dateTime()->placeholder('—'),
                Tables\Columns\TextColumn::make('products_count')->label('محصول')->counts('products'),
                Tables\Columns\IconColumn::make('is_featured')->label('ویژه')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageDrops::route('/')];
    }
}
