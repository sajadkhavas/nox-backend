<?php

namespace App\Filament\Resources;

use App\Enums\PublicationStatus;
use App\Filament\Resources\CollectionResource\Pages;
use App\Models\Collection;
use App\Models\Collection as ApparelCollection;
use App\Support\StorefrontMediaOptions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CollectionResource extends Resource
{
    protected static ?string $model = ApparelCollection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'کالکشن‌ها';

    protected static ?string $modelLabel = 'کالکشن';

    protected static ?string $pluralModelLabel = 'کالکشن‌ها';

    protected static ?string $navigationGroup = 'دامنه پوشاک';

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('کالکشن')->schema([
                Forms\Components\TextInput::make('name')->label('نام')->required()->maxLength(180),
                Forms\Components\TextInput::make('slug')->label('Slug')->maxLength(200),
                Forms\Components\Textarea::make('description')->label('توضیحات')->columnSpanFull(),
                Forms\Components\Select::make('publication_status')
                    ->label('وضعیت انتشار')
                    ->options(self::publicationOptions())
                    ->default(PublicationStatus::Draft->value)
                    ->required(),
                Forms\Components\Toggle::make('is_featured')->label('ویژه'),
                Forms\Components\TextInput::make('sort_order')->label('ترتیب')->numeric()->minValue(0)->default(0),
                Forms\Components\Select::make('cover_image_path')
                    ->label('کاور کالکشن از کتابخانه')
                    ->options(fn (?Collection $record): array => StorefrontMediaOptions::paths($record?->cover_image_path))
                    ->searchable()->columnSpanFull(),
                Forms\Components\Select::make('products')
                    ->label('محصولات')
                    ->relationship('products', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('SEO')->schema([
                Forms\Components\TextInput::make('meta_title')->label('Meta title')->maxLength(70),
                Forms\Components\Textarea::make('meta_description')->label('Meta description')->maxLength(180),
            ])->columns(2)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image_path')->label('کاور')->disk('public')->square(),
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('publication_status')->label('انتشار')->badge(),
                Tables\Columns\TextColumn::make('products_count')->label('محصول')->counts('products'),
                Tables\Columns\IconColumn::make('is_featured')->label('ویژه')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('ترتیب')->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCollections::route('/')];
    }

    private static function publicationOptions(): array
    {
        return collect(PublicationStatus::cases())
            ->mapWithKeys(fn (PublicationStatus $status): array => [$status->value => $status->value])
            ->all();
    }
}
