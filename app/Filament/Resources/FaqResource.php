<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'سؤالات متداول';

    protected static ?string $modelLabel = 'سؤال متداول';

    protected static ?string $pluralModelLabel = 'سؤالات متداول';

    protected static ?string $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('category')
                ->label('دسته')
                ->required()
                ->default('general')
                ->maxLength(100),
            Forms\Components\TextInput::make('sort_order')
                ->label('ترتیب')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('فعال')
                ->default(true),
            Forms\Components\TextInput::make('question')
                ->label('سؤال')
                ->required()
                ->maxLength(500)
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('answer')
                ->label('پاسخ')
                ->required()
                ->columnSpanFull(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')->label('سؤال')->searchable()->limit(70),
                Tables\Columns\TextColumn::make('category')->label('دسته')->badge()->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('ترتیب')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('دسته')
                    ->options(fn (): array => Faq::query()->distinct()->orderBy('category')->pluck('category', 'category')->all()),
                Tables\Filters\TernaryFilter::make('is_active')->label('فعال'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageFaqs::route('/')];
    }
}
