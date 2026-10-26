<?php

namespace App\Filament\Resources;

use App\Enums\ReturnResolution;
use App\Enums\ReturnStatus;
use App\Filament\Resources\ReturnRequestResource\Pages;
use App\Models\ReturnRequest;
use App\Services\Commerce\ReturnService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReturnRequestResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;

    protected static ?string $navigationLabel = 'مرجوعی‌ها';

    protected static ?string $navigationGroup = 'عملیات فروش';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') === true;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('public_id')->label('ID')->copyable(), Tables\Columns\TextColumn::make('order.order_number')->label('سفارش')->searchable(),
            Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('resolution')->badge(),
            Tables\Columns\TextColumn::make('reason')->limit(45), Tables\Columns\TextColumn::make('requested_at')->dateTime()->sortable(),
        ])->actions([
            Tables\Actions\Action::make('approve')->label('تأیید')->requiresConfirmation()->visible(fn (ReturnRequest $r): bool => $r->status === ReturnStatus::Requested)
                ->action(fn (ReturnRequest $r) => app(ReturnService::class)->approve($r, auth()->id())),
            Tables\Actions\Action::make('reject')->label('رد')->color('danger')->visible(fn (ReturnRequest $r): bool => $r->status === ReturnStatus::Requested)
                ->form([Forms\Components\Textarea::make('note')->required()->maxLength(1000)])
                ->action(fn (ReturnRequest $r, array $data) => app(ReturnService::class)->reject($r, auth()->id(), (string) $data['note'])),
            Tables\Actions\Action::make('receive')->label('دریافت کالا')->requiresConfirmation()->visible(fn (ReturnRequest $r): bool => $r->status === ReturnStatus::Approved)
                ->action(fn (ReturnRequest $r) => app(ReturnService::class)->receive($r, auth()->id())),
            Tables\Actions\Action::make('resolve')->label('نهایی‌سازی')->visible(fn (ReturnRequest $r): bool => $r->status === ReturnStatus::Received)
                ->form([
                    Forms\Components\Select::make('resolution')->options(collect(ReturnResolution::cases())->mapWithKeys(fn ($v) => [$v->value => $v->value]))->required(),
                    Forms\Components\Toggle::make('restock')->label('بازگشت به موجودی')->default(false),
                    Forms\Components\Textarea::make('note')->maxLength(1000),
                ])->action(fn (ReturnRequest $r, array $data) => app(ReturnService::class)->resolve($r, ReturnResolution::from($data['resolution']), (bool) $data['restock'], auth()->id(), $data['note'] ?? null)),
        ])->bulkActions([])->defaultSort('requested_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListReturnRequests::route('/')];
    }
}
