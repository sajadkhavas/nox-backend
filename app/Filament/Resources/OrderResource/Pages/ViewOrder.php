<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderLifecycleService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('تأیید سفارش')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->order()->status === OrderStatus::Paid)
                ->action(function (): void {
                    $this->transition(OrderStatus::Confirmed);
                }),
            Actions\Action::make('prepare')
                ->label('شروع آماده‌سازی')
                ->icon('heroicon-o-fire')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->order()->status === OrderStatus::Confirmed)
                ->action(function (): void {
                    $this->transition(OrderStatus::Preparing);
                }),
            Actions\Action::make('ready')
                ->label('آماده شد')
                ->icon('heroicon-o-gift')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->order()->status === OrderStatus::Preparing)
                ->action(function (): void {
                    $this->transition(OrderStatus::Ready);
                }),
            Actions\Action::make('dispatch')
                ->label('ثبت ارسال')
                ->icon('heroicon-o-truck')
                ->color('primary')
                ->form([
                    Forms\Components\TextInput::make('trackingCode')
                        ->label('کد پیگیری')
                        ->required()
                        ->maxLength(160),
                    Forms\Components\Textarea::make('note')->label('یادداشت')->maxLength(1000),
                ])
                ->visible(fn (): bool => $this->order()->status === OrderStatus::Ready
                    && $this->order()->delivery_method !== DeliveryMethod::Pickup)
                ->action(function (array $data): void {
                    $this->transition(
                        OrderStatus::Dispatched,
                        $data['note'] ?? null,
                        $data['trackingCode'] ?? null,
                    );
                }),
            Actions\Action::make('deliver')
                ->label('ثبت تحویل')
                ->icon('heroicon-o-home')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->order()->status === OrderStatus::Dispatched
                    || ($this->order()->status === OrderStatus::Ready
                        && $this->order()->delivery_method === DeliveryMethod::Pickup))
                ->action(function (): void {
                    $this->transition(OrderStatus::Delivered);
                }),
            Actions\Action::make('cancel')
                ->label('لغو سفارش')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Forms\Components\Textarea::make('note')
                        ->label('دلیل لغو')
                        ->required()
                        ->maxLength(1000),
                ])
                ->visible(fn (): bool => in_array($this->order()->status, [
                    OrderStatus::AwaitingPayment,
                    OrderStatus::Paid,
                    OrderStatus::Confirmed,
                    OrderStatus::Preparing,
                    OrderStatus::Ready,
                ], true))
                ->action(function (array $data): void {
                    $this->transition(
                        OrderStatus::Cancelled,
                        $data['note'] ?? null,
                    );
                }),
            Actions\Action::make('internalNote')
                ->label('یادداشت داخلی')
                ->icon('heroicon-o-pencil-square')
                ->form([
                    Forms\Components\Textarea::make('note')
                        ->label('یادداشت')
                        ->required()
                        ->maxLength(3000),
                ])
                ->action(function (array $data): void {
                    app(OrderLifecycleService::class)->addInternalNote(
                        $this->order(),
                        auth()->id(),
                        (string) $data['note'],
                    );
                    $this->reloadPage();
                }),
        ];
    }

    private function transition(
        OrderStatus $target,
        ?string $note = null,
        ?string $trackingCode = null,
    ): void {
        app(OrderLifecycleService::class)->transitionByAdmin(
            $this->order(),
            $target,
            auth()->id(),
            $note,
            $trackingCode,
        );
        $this->reloadPage();
    }

    private function reloadPage(): void
    {
        $this->redirect(OrderResource::getUrl('view', ['record' => $this->record]));
    }

    private function order(): Order
    {
        /** @var Order $order */
        $order = $this->record;

        return $order;
    }
}
