<?php

namespace App\Filament\Resources;

use App\Enums\ExchangeStatus;
use App\Filament\Resources\ExchangeRequestResource\Pages;
use App\Models\ExchangeRequest;
use App\Services\Commerce\ExchangeService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExchangeRequestResource extends Resource
{
    protected static ?string $model = ExchangeRequest::class;

    protected static ?string $navigationLabel = 'تعویض‌ها';

    protected static ?string $navigationGroup = 'عملیات فروش';

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') === true;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('public_id')->label('ID')->copyable(), Tables\Columns\TextColumn::make('order.order_number')->label('سفارش')->searchable(),
            Tables\Columns\TextColumn::make('orderItem.sku')->label('SKU مبدا'), Tables\Columns\TextColumn::make('destinationVariant.sku')->label('SKU مقصد'),
            Tables\Columns\TextColumn::make('quantity')->numeric(), Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('requested_at')->dateTime(),
        ])->actions([
            Tables\Actions\Action::make('approve')->label('تأیید و رزرو مقصد')->requiresConfirmation()->visible(fn (ExchangeRequest $r): bool => $r->status === ExchangeStatus::Requested)
                ->action(fn (ExchangeRequest $r) => app(ExchangeService::class)->approve($r, auth()->id())),
            Tables\Actions\Action::make('reject')->label('رد')->color('danger')->visible(fn (ExchangeRequest $r): bool => in_array($r->status, [ExchangeStatus::Requested, ExchangeStatus::Approved], true))
                ->form([Forms\Components\Textarea::make('note')->required()->maxLength(1000)])
                ->action(fn (ExchangeRequest $r, array $data) => app(ExchangeService::class)->reject($r, auth()->id(), (string) $data['note'])),
            Tables\Actions\Action::make('complete')->label('تکمیل تعویض')->requiresConfirmation()->visible(fn (ExchangeRequest $r): bool => $r->status === ExchangeStatus::Approved)
                ->action(fn (ExchangeRequest $r) => app(ExchangeService::class)->complete($r, auth()->id())),
        ])->bulkActions([])->defaultSort('requested_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListExchangeRequests::route('/')];
    }
}
