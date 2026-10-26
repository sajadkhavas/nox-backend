<?php

namespace App\Filament\Resources;

use App\Enums\RefundStatus;
use App\Filament\Resources\RefundRequestResource\Pages;
use App\Models\RefundRequest;
use App\Services\Commerce\RefundService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RefundRequestResource extends Resource
{
    protected static ?string $model = RefundRequest::class;

    protected static ?string $navigationLabel = 'بازپرداخت‌ها';

    protected static ?string $navigationGroup = 'عملیات فروش';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') === true;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('public_id')->label('ID')->copyable(), Tables\Columns\TextColumn::make('order.order_number')->label('سفارش')->searchable(),
            Tables\Columns\TextColumn::make('amount_toman')->numeric()->suffix(' تومان'), Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('provider'), Tables\Columns\TextColumn::make('provider_reference')->copyable(), Tables\Columns\TextColumn::make('requested_at')->dateTime(),
        ])->actions([
            Tables\Actions\Action::make('pending')->label('ارسال به Provider')->requiresConfirmation()
                ->visible(fn (RefundRequest $r): bool => $r->status === RefundStatus::Requested && config('lbb.payment.refunds_enabled', false))
                ->action(fn (RefundRequest $r) => app(RefundService::class)->markPending($r, auth()->id())),
            Tables\Actions\Action::make('failed')->label('ثبت شکست')->color('danger')
                ->visible(fn (RefundRequest $r): bool => in_array($r->status, [RefundStatus::Requested, RefundStatus::Pending], true))
                ->form([Forms\Components\Textarea::make('message')->required()->maxLength(1000)])
                ->action(fn (RefundRequest $r, array $data) => app(RefundService::class)->markFailed($r, (string) $data['message'], auth()->id())),
        ])->bulkActions([])->defaultSort('requested_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRefundRequests::route('/')];
    }
}
