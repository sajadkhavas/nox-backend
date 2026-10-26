<?php

namespace App\Filament\Resources;

use App\Enums\NotificationStatus;
use App\Filament\Resources\NotificationOutboxResource\Pages;
use App\Models\NotificationOutbox;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationOutboxResource extends Resource
{
    protected static ?string $model = NotificationOutbox::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationLabel = 'صف اعلان‌ها';

    protected static ?string $modelLabel = 'اعلان';

    protected static ?string $pluralModelLabel = 'صف اعلان‌ها';

    protected static ?string $navigationGroup = 'فروشگاه LBB';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('public_id')->label('شناسه')->disabled(),
            Forms\Components\TextInput::make('order.order_number')->label('سفارش')->disabled(),
            Forms\Components\TextInput::make('template_key')->label('قالب')->disabled(),
            Forms\Components\TextInput::make('provider')->label('ارائه‌دهنده')->disabled(),
            Forms\Components\TextInput::make('status')->label('وضعیت')->disabled(),
            Forms\Components\TextInput::make('attempts')->label('تعداد تلاش')->disabled(),
            Forms\Components\TextInput::make('provider_message_id')->label('شناسه پیام')->disabled()->columnSpanFull(),
            Forms\Components\KeyValue::make('payload')->label('داده قالب')->disabled()->columnSpanFull(),
            Forms\Components\Textarea::make('last_error')->label('آخرین خطا')->disabled()->rows(4)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('public_id')->label('شناسه')->copyable()->limit(12),
                Tables\Columns\TextColumn::make('order.order_number')->label('سفارش')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('destination')
                    ->label('مقصد')
                    ->formatStateUsing(fn (?string $state): string => self::maskDestination($state)),
                Tables\Columns\TextColumn::make('template_key')->label('قالب')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (NotificationStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('provider')->label('Provider')->badge(),
                Tables\Columns\TextColumn::make('attempts')->label('تلاش')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('ایجاد')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(collect(NotificationStatus::cases())->mapWithKeys(
                        fn (NotificationStatus $status): array => [$status->value => $status->label()],
                    )),
                Tables\Filters\SelectFilter::make('provider')
                    ->label('Provider')
                    ->options(fn (): array => NotificationOutbox::query()->distinct()->pluck('provider', 'provider')->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('retry')
                    ->label('ارسال مجدد')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (NotificationOutbox $record): bool => in_array($record->status, [
                        NotificationStatus::Failed,
                        NotificationStatus::Cancelled,
                    ], true))
                    ->action(fn (NotificationOutbox $record): bool => $record->update([
                        'status' => NotificationStatus::Pending,
                        'available_at' => now(),
                        'failed_at' => null,
                        'last_error' => null,
                    ])),
                Tables\Actions\EditAction::make()->label('جزئیات'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageNotificationOutbox::route('/')];
    }

    private static function maskDestination(?string $destination): string
    {
        $destination = (string) $destination;
        if (mb_strlen($destination) <= 4) {
            return '****';
        }

        return mb_substr($destination, 0, 3).'****'.mb_substr($destination, -4);
    }
}
