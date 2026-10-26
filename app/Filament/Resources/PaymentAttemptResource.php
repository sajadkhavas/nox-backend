<?php

namespace App\Filament\Resources;

use App\Enums\PaymentAttemptStatus;
use App\Filament\Resources\PaymentAttemptResource\Pages;
use App\Models\PaymentAttempt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentAttemptResource extends Resource
{
    protected static ?string $model = PaymentAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'تلاش‌های پرداخت';

    protected static ?string $modelLabel = 'تلاش پرداخت';

    protected static ?string $pluralModelLabel = 'تلاش‌های پرداخت';

    protected static ?string $navigationGroup = 'فروشگاه LBB';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('پرداخت')
                ->schema([
                    Forms\Components\TextInput::make('public_id')->label('شناسه')->disabled(),
                    Forms\Components\TextInput::make('order.order_number')->label('شماره سفارش')->disabled(),
                    Forms\Components\TextInput::make('provider')->label('درگاه')->disabled(),
                    Forms\Components\TextInput::make('attempt_number')->label('شماره تلاش')->disabled(),
                    Forms\Components\Select::make('status')
                        ->label('وضعیت')
                        ->options(collect(PaymentAttemptStatus::cases())->mapWithKeys(
                            fn (PaymentAttemptStatus $status): array => [$status->value => $status->label()],
                        ))
                        ->disabled(),
                    Forms\Components\TextInput::make('amount_toman')->label('مبلغ تومان')->disabled(),
                    Forms\Components\TextInput::make('authority')->label('Authority')->disabled(),
                    Forms\Components\TextInput::make('reference_id')->label('شماره مرجع')->disabled(),
                    Forms\Components\TextInput::make('gateway_code')->label('کد درگاه')->disabled(),
                    Forms\Components\TextInput::make('failure_code')->label('کد خطا')->disabled(),
                    Forms\Components\Textarea::make('failure_message')
                        ->label('پیام خطا')
                        ->disabled()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('expires_at')->label('انقضا')->disabled(),
                    Forms\Components\TextInput::make('verified_at')->label('زمان تأیید')->disabled(),
                    Forms\Components\TextInput::make('created_at')->label('زمان ایجاد')->disabled(),
                ])
                ->columns(3),
            Forms\Components\Section::make('داده‌های پاک‌سازی‌شده درگاه')
                ->schema([
                    Forms\Components\Textarea::make('request_payload')
                        ->label('درخواست')
                        ->formatStateUsing(fn (?array $state): string => self::json($state))
                        ->disabled(),
                    Forms\Components\Textarea::make('response_payload')
                        ->label('پاسخ ایجاد')
                        ->formatStateUsing(fn (?array $state): string => self::json($state))
                        ->disabled(),
                    Forms\Components\Textarea::make('verification_payload')
                        ->label('پاسخ تأیید')
                        ->formatStateUsing(fn (?array $state): string => self::json($state))
                        ->disabled(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('سفارش')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('درگاه')
                    ->badge(),
                Tables\Columns\TextColumn::make('attempt_number')
                    ->label('تلاش')
                    ->numeric(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (PaymentAttemptStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('amount_toman')
                    ->label('مبلغ')
                    ->numeric()
                    ->suffix(' تومان')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('مرجع')
                    ->copyable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('زمان')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->label('درگاه')
                    ->options([
                        'testing' => 'آزمایشی',
                        'zarinpal' => 'زرین‌پال',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(collect(PaymentAttemptStatus::cases())->mapWithKeys(
                        fn (PaymentAttemptStatus $status): array => [$status->value => $status->label()],
                    )),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
        return [
            'index' => Pages\ListPaymentAttempts::route('/'),
            'view' => Pages\ViewPaymentAttempt::route('/{record}'),
        ];
    }

    private static function json(?array $state): string
    {
        return $state === null
            ? ''
            : (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
