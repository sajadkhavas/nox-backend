<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentPageResource\Pages;
use App\Models\ContentPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use FilamentTiptapEditor\TiptapEditor;

class ContentPageResource extends Resource
{
    protected static ?string $model = ContentPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'صفحات فروشگاه';

    protected static ?string $modelLabel = 'صفحه';

    protected static ?string $pluralModelLabel = 'صفحات فروشگاه';

    protected static ?string $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('محتوا')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('نوع')
                        ->options([
                            'page' => 'صفحه عمومی',
                            'legal' => 'قوانین و حریم خصوصی',
                            'shipping' => 'ارسال و تحویل',
                            'homepage' => 'محتوای صفحه اصلی',
                        ])
                        ->default('page')
                        ->required(),
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(160),
                    Forms\Components\TextInput::make('title')
                        ->label('عنوان')
                        ->required()
                        ->maxLength(220)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('excerpt')
                        ->label('خلاصه')
                        ->rows(3)
                        ->columnSpanFull(),
                    TiptapEditor::make('content')
                        ->tools(['heading', 'bullet-list', 'ordered-list', '|', 'bold', 'italic', 'link', 'media'])
                        ->label('متن')
                        ->columnSpanFull(),
                ])->columns(2),
            Forms\Components\Section::make('انتشار و سئو')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('وضعیت')
                        ->options(['draft' => 'پیش‌نویس', 'published' => 'منتشرشده'])
                        ->default('draft')
                        ->required(),
                    Forms\Components\DateTimePicker::make('published_at')->label('زمان انتشار'),
                    Forms\Components\TextInput::make('meta_title')->label('عنوان سئو')->maxLength(220),
                    Forms\Components\Textarea::make('meta_description')->label('توضیح سئو')->rows(3),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('عنوان')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('type')->label('نوع')->badge(),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')->badge(),
                Tables\Columns\TextColumn::make('published_at')->label('انتشار')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(['draft' => 'پیش‌نویس', 'published' => 'منتشرشده']),
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع')
                    ->options([
                        'page' => 'صفحه عمومی',
                        'legal' => 'قوانین',
                        'shipping' => 'ارسال',
                        'homepage' => 'صفحه اصلی',
                    ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageContentPages::route('/'),
        ];
    }
}
