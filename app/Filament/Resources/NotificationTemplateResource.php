<?php

namespace App\Filament\Resources;

use App\Enums\NotificationChannel;
use App\Filament\Resources\NotificationTemplateResource\Pages;
use App\Models\NotificationTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'قالب‌های اعلان';

    protected static ?string $modelLabel = 'قالب اعلان';

    protected static ?string $pluralModelLabel = 'قالب‌های اعلان';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key')
                ->label('کلید')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(120),
            Forms\Components\Select::make('channel')
                ->label('کانال')
                ->options(collect(NotificationChannel::cases())->mapWithKeys(
                    fn (NotificationChannel $channel): array => [$channel->value => $channel->label()],
                ))
                ->default(NotificationChannel::Sms->value)
                ->required(),
            Forms\Components\TextInput::make('provider_template')
                ->label('شناسه قالب ارائه‌دهنده')
                ->maxLength(120),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
            Forms\Components\Textarea::make('body')
                ->label('متن قالب')
                ->helperText('متغیرها را به شکل {{order_number}} یا {{tracking_code}} بنویسید.')
                ->required()
                ->rows(6)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')->label('کلید')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('channel')
                    ->label('کانال')
                    ->badge()
                    ->formatStateUsing(fn (NotificationChannel $state): string => $state->label()),
                Tables\Columns\TextColumn::make('provider_template')->label('قالب Provider')->placeholder('بدون قالب'),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('آخرین تغییر')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([Tables\Filters\TernaryFilter::make('is_active')->label('فعال')])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([])
            ->defaultSort('key');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageNotificationTemplates::route('/')];
    }
}
