<?php

namespace App\Filament\Resources;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'سفارش‌ها';

    protected static ?string $modelLabel = 'سفارش';

    protected static ?string $pluralModelLabel = 'سفارش‌ها';

    protected static ?string $navigationGroup = 'فروشگاه LBB';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('وضعیت سفارش')
                ->schema([
                    Forms\Components\TextInput::make('order_number')->label('شماره سفارش')->disabled(),
                    Forms\Components\Select::make('status')
                        ->label('وضعیت')
                        ->options(collect(OrderStatus::cases())->mapWithKeys(
                            fn (OrderStatus $status): array => [$status->value => $status->label()],
                        ))
                        ->disabled(),
                    Forms\Components\Select::make('payment_status')
                        ->label('وضعیت پرداخت')
                        ->options(collect(PaymentStatus::cases())->mapWithKeys(
                            fn (PaymentStatus $status): array => [$status->value => $status->label()],
                        ))
                        ->disabled(),
                    Forms\Components\Select::make('delivery_method')
                        ->label('روش تحویل')
                        ->options(collect(DeliveryMethod::cases())->mapWithKeys(
                            fn (DeliveryMethod $method): array => [$method->value => $method->label()],
                        ))
                        ->disabled(),
                    Forms\Components\TextInput::make('deliveryZone.name')->label('منطقه ارسال')->disabled(),
                    Forms\Components\TextInput::make('tracking_code')->label('کد پیگیری')->disabled(),
                    Forms\Components\TextInput::make('reservation_expires_at')->label('پایان رزرو')->disabled(),
                    Forms\Components\TextInput::make('placed_at')->label('زمان ثبت')->disabled(),
                    Forms\Components\TextInput::make('paid_at')->label('زمان پرداخت')->disabled(),
                    Forms\Components\TextInput::make('confirmed_at')->label('زمان تأیید')->disabled(),
                    Forms\Components\TextInput::make('preparing_at')->label('شروع آماده‌سازی')->disabled(),
                    Forms\Components\TextInput::make('ready_at')->label('زمان آماده‌شدن')->disabled(),
                    Forms\Components\TextInput::make('dispatched_at')->label('زمان ارسال')->disabled(),
                    Forms\Components\TextInput::make('delivered_at')->label('زمان تحویل')->disabled(),
                    Forms\Components\TextInput::make('cancelled_at')->label('زمان لغو')->disabled(),
                ])
                ->columns(3),
            Forms\Components\Section::make('گیرنده و ارسال')
                ->schema([
                    Forms\Components\TextInput::make('customer_name')->label('نام گیرنده')->disabled(),
                    Forms\Components\TextInput::make('customer_mobile')->label('موبایل')->disabled(),
                    Forms\Components\TextInput::make('province')->label('استان')->disabled(),
                    Forms\Components\TextInput::make('city')->label('شهر')->disabled(),
                    Forms\Components\Textarea::make('address')->label('آدرس')->disabled()->columnSpanFull(),
                    Forms\Components\TextInput::make('postal_code')->label('کد پستی')->disabled(),
                    Forms\Components\Textarea::make('notes')->label('یادداشت مشتری')->disabled()->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('مبالغ و آماده‌سازی')
                ->schema([
                    Forms\Components\TextInput::make('subtotal_toman')->label('جمع اقلام')->disabled(),
                    Forms\Components\TextInput::make('delivery_fee_toman')->label('هزینه ارسال')->disabled(),
                    Forms\Components\TextInput::make('packaging_fee_toman')->label('هزینه بسته‌بندی')->disabled(),
                    Forms\Components\TextInput::make('discount_total_toman')->label('تخفیف')->disabled(),
                    Forms\Components\TextInput::make('grand_total_toman')->label('مبلغ نهایی')->disabled(),
                    Forms\Components\TextInput::make('preparation_time_days')->label('حداقل روز آماده‌سازی')->disabled(),
                    Forms\Components\TextInput::make('preparation_max_days')->label('حداکثر روز آماده‌سازی')->disabled(),
                ])
                ->columns(4),
            Forms\Components\Repeater::make('items')
                ->label('اقلام سفارش')
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('product_name')->label('محصول')->disabled(),
                    Forms\Components\TextInput::make('variant_name')->label('انتخاب')->disabled(),
                    Forms\Components\TextInput::make('sku')->label('SKU')->disabled(),
                    Forms\Components\TextInput::make('unit_price_toman')->label('قیمت واحد')->disabled(),
                    Forms\Components\TextInput::make('quantity')->label('تعداد')->disabled(),
                    Forms\Components\TextInput::make('line_total_toman')->label('جمع')->disabled(),
                ])
                ->columns(3)
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columnSpanFull(),
            Forms\Components\Repeater::make('statusHistory')
                ->label('تاریخچه وضعیت')
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('from_status')->label('از')->disabled(),
                    Forms\Components\TextInput::make('to_status')->label('به')->disabled(),
                    Forms\Components\TextInput::make('actor_type')->label('عامل')->disabled(),
                    Forms\Components\Textarea::make('note')->label('شرح')->disabled()->columnSpanFull(),
                    Forms\Components\TextInput::make('created_at')->label('زمان')->disabled(),
                ])
                ->columns(3)
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columnSpanFull(),
            Forms\Components\Repeater::make('internalNotes')
                ->label('یادداشت‌های داخلی')
                ->relationship()
                ->schema([
                    Forms\Components\Textarea::make('note')->label('یادداشت')->disabled()->columnSpanFull(),
                    Forms\Components\TextInput::make('created_at')->label('زمان')->disabled(),
                ])
                ->columns(2)
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('شماره سفارش')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('customer_name')->label('مشتری')->searchable(),
                Tables\Columns\TextColumn::make('customer_mobile')->label('موبایل')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('پرداخت')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('delivery_method')
                    ->label('تحویل')
                    ->formatStateUsing(fn (DeliveryMethod $state): string => $state->label()),
                Tables\Columns\TextColumn::make('deliveryZone.name')->label('منطقه')->placeholder('Fallback'),
                Tables\Columns\TextColumn::make('grand_total_toman')
                    ->label('مبلغ نهایی')
                    ->numeric()
                    ->suffix(' تومان')
                    ->sortable(),
                Tables\Columns\TextColumn::make('placed_at')->label('زمان ثبت')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(
                        fn (OrderStatus $status): array => [$status->value => $status->label()],
                    )),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('پرداخت')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(
                        fn (PaymentStatus $status): array => [$status->value => $status->label()],
                    )),
                Tables\Filters\SelectFilter::make('delivery_method')
                    ->label('تحویل')
                    ->options(collect(DeliveryMethod::cases())->mapWithKeys(
                        fn (DeliveryMethod $method): array => [$method->value => $method->label()],
                    )),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->bulkActions([])
            ->defaultSort('placed_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
