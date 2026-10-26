<?php

namespace App\Services\Commerce;

use App\Enums\CommerceErrorCode;
use App\Enums\ExchangeStatus;
use App\Enums\InventoryLedgerEventType;
use App\Enums\InventoryReservationStatus;
use App\Enums\OrderStatus;
use App\Enums\PublicationStatus;
use App\Exceptions\CommerceException;
use App\Exceptions\IdempotencyConflict;
use App\Models\Customer;
use App\Models\ExchangeRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class ExchangeService
{
    public function __construct(
        private readonly InventoryLedgerService $inventory,
        private readonly CommerceAuditService $audit,
    ) {}

    /** @return array{exchange: ExchangeRequest,replayed: bool} */
    public function request(Customer $customer, Order $order, array $payload, string $idempotencyKey): array
    {
        $canonical = [
            'orderId' => $order->public_id,
            'orderItemId' => trim((string) ($payload['orderItemId'] ?? '')),
            'destinationVariantId' => trim((string) ($payload['destinationVariantId'] ?? '')),
            'quantity' => (int) ($payload['quantity'] ?? 0),
            'reason' => trim((string) ($payload['reason'] ?? '')),
        ];
        $requestHash = hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $existing = ExchangeRequest::query()->where('customer_id', $customer->getKey())->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            if (! hash_equals($existing->request_hash, $requestHash)) {
                throw new IdempotencyConflict;
            }

            return ['exchange' => $existing->load(['orderItem', 'destinationVariant']), 'replayed' => true];
        }

        try {
            return DB::transaction(function () use ($customer, $order, $canonical, $idempotencyKey, $requestHash): array {
                $lockedOrder = Order::query()->ownedBy($customer)->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== OrderStatus::Delivered) {
                    throw new CommerceException(CommerceErrorCode::ExchangeUnavailable, 'تعویض فقط برای سفارش تحویل‌شده قابل درخواست است.', 409);
                }
                $line = OrderItem::query()
                    ->where('order_id', $lockedOrder->getKey())
                    ->where('public_id', $canonical['orderItemId'])
                    ->lockForUpdate()
                    ->first();
                if (! $line) {
                    throw new CommerceException(CommerceErrorCode::ExchangeUnavailable, 'قلم سفارش معتبر نیست.', 422);
                }
                $quantity = (int) $canonical['quantity'];
                if ($quantity < 1 || $quantity > (int) $line->quantity) {
                    throw new CommerceException(CommerceErrorCode::InvalidQuantity, 'تعداد تعویض معتبر نیست.', 422);
                }

                $used = (int) ExchangeRequest::query()
                    ->where('order_item_id', $line->getKey())
                    ->whereNotIn('status', [ExchangeStatus::Rejected->value, ExchangeStatus::Cancelled->value])
                    ->sum('quantity');
                if ($used + $quantity > (int) $line->quantity) {
                    throw new CommerceException(CommerceErrorCode::InvalidQuantity, 'تعداد تعویض از تعداد خرید بیشتر است.', 409);
                }

                $destination = ProductVariant::query()
                    ->where('public_id', $canonical['destinationVariantId'])
                    ->with(['product', 'color', 'size'])
                    ->lockForUpdate()
                    ->first();
                if (! $destination
                    || ! $destination->is_active
                    || $destination->product_id !== $line->product_id
                    || $destination->product?->publication_status !== PublicationStatus::Published
                    || ! $destination->product?->is_active
                    || ! $destination->color?->is_active
                    || ! $destination->size?->is_active
                ) {
                    throw new CommerceException(CommerceErrorCode::ExchangeUnavailable, 'Variant مقصد برای تعویض معتبر نیست.', 409);
                }
                if ($line->variant_id !== null && $destination->getKey() === $line->variant_id) {
                    throw new CommerceException(CommerceErrorCode::ExchangeUnavailable, 'Variant مقصد باید با Variant خریداری‌شده متفاوت باشد.', 422);
                }
                if ($this->inventory->available($destination) < $quantity) {
                    throw new CommerceException(CommerceErrorCode::OutOfStock, 'Variant مقصد موجودی کافی ندارد.', 409);
                }

                $exchange = ExchangeRequest::query()->create([
                    'customer_id' => $customer->getKey(),
                    'order_id' => $lockedOrder->getKey(),
                    'order_item_id' => $line->getKey(),
                    'source_variant_id' => $line->variant_id,
                    'destination_variant_id' => $destination->getKey(),
                    'idempotency_key' => $idempotencyKey,
                    'request_hash' => $requestHash,
                    'quantity' => $quantity,
                    'status' => ExchangeStatus::Requested,
                    'reason' => $canonical['reason'] ?: null,
                    'requested_at' => now(),
                ]);
                $this->audit->record('exchange.requested', 'exchange_request', $exchange->public_id, $lockedOrder, 'customer', $customer->getKey(), [
                    'destinationVariantId' => $destination->public_id,
                    'quantity' => $quantity,
                ]);

                return ['exchange' => $exchange->load(['orderItem', 'destinationVariant']), 'replayed' => false];
            }, 3);
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw $exception;
            }
            $existing = ExchangeRequest::query()->where('customer_id', $customer->getKey())->where('idempotency_key', $idempotencyKey)->first();
            if (! $existing || ! hash_equals($existing->request_hash, $requestHash)) {
                throw $exception;
            }

            return ['exchange' => $existing->load(['orderItem', 'destinationVariant']), 'replayed' => true];
        }
    }

    public function approve(ExchangeRequest $exchange, ?int $adminId, ?string $note = null): ExchangeRequest
    {
        return DB::transaction(function () use ($exchange, $adminId, $note): ExchangeRequest {
            $locked = ExchangeRequest::query()->whereKey($exchange->getKey())->lockForUpdate()->with(['order', 'destinationVariant'])->firstOrFail();
            if ($locked->status !== ExchangeStatus::Requested) {
                throw new CommerceException(CommerceErrorCode::InvalidTransition, 'تعویض در وضعیت قابل تأیید نیست.', 409);
            }
            $reservation = $this->inventory->reserveForOrder(
                $locked->order,
                $locked->destinationVariant,
                (int) $locked->quantity,
                now()->addMinutes(max(1, (int) config('lbb.commerce.exchange_reservation_minutes', 60))),
                'exchange',
                "exchange:{$locked->public_id}:destination",
                'admin',
                $adminId,
            );
            $locked->forceFill([
                'status' => ExchangeStatus::Approved,
                'destination_reservation_id' => $reservation->getKey(),
                'approved_at' => now(),
                'admin_note' => $this->mergeNote($locked->admin_note, $note),
            ])->save();
            $this->audit->record('exchange.approved', 'exchange_request', $locked->public_id, $locked->order, 'admin', $adminId, [
                'reservationId' => $reservation->public_id,
            ]);

            return $locked->fresh(['destinationReservation', 'destinationVariant', 'orderItem']);
        }, 3);
    }

    public function reject(ExchangeRequest $exchange, ?int $adminId, string $note): ExchangeRequest
    {
        return DB::transaction(function () use ($exchange, $adminId, $note): ExchangeRequest {
            $locked = ExchangeRequest::query()->whereKey($exchange->getKey())->lockForUpdate()->with(['order', 'destinationReservation'])->firstOrFail();
            if (! in_array($locked->status, [ExchangeStatus::Requested, ExchangeStatus::Approved], true)) {
                throw new CommerceException(CommerceErrorCode::InvalidTransition, 'تعویض در وضعیت قابل رد نیست.', 409);
            }
            if ($locked->destinationReservation?->status === InventoryReservationStatus::Active) {
                $this->inventory->release($locked->destinationReservation, InventoryReservationStatus::Released, 'exchange_rejected', 'admin', $adminId);
            }
            $locked->forceFill([
                'status' => ExchangeStatus::Rejected,
                'rejected_at' => now(),
                'admin_note' => $this->mergeNote($locked->admin_note, $note),
            ])->save();
            $this->audit->record('exchange.rejected', 'exchange_request', $locked->public_id, $locked->order, 'admin', $adminId);

            return $locked->fresh(['destinationReservation']);
        }, 3);
    }

    public function complete(ExchangeRequest $exchange, ?int $adminId, ?string $note = null): ExchangeRequest
    {
        return DB::transaction(function () use ($exchange, $adminId, $note): ExchangeRequest {
            $locked = ExchangeRequest::query()
                ->whereKey($exchange->getKey())
                ->lockForUpdate()
                ->with(['order', 'orderItem.variant', 'destinationVariant', 'destinationReservation'])
                ->firstOrFail();
            if ($locked->status === ExchangeStatus::Completed) {
                return $locked;
            }
            if ($locked->status !== ExchangeStatus::Approved || ! $locked->destinationReservation) {
                throw new CommerceException(CommerceErrorCode::InvalidTransition, 'تعویض در وضعیت قابل تکمیل نیست.', 409);
            }

            $this->inventory->consume($locked->destinationReservation, 'admin', $adminId);
            $source = $locked->orderItem?->variant;
            if ($source) {
                $this->inventory->recordReturnStock(
                    $source,
                    (int) $locked->quantity,
                    'exchange_request',
                    $locked->public_id,
                    $locked->order,
                    'exchange_source_returned',
                    "exchange:return-source:{$locked->public_id}",
                    'admin',
                    $adminId,
                    InventoryLedgerEventType::ExchangeIn,
                );
            }

            $locked->forceFill([
                'status' => ExchangeStatus::Completed,
                'completed_at' => now(),
                'admin_note' => $this->mergeNote($locked->admin_note, $note),
            ])->save();
            $this->audit->record('exchange.completed', 'exchange_request', $locked->public_id, $locked->order, 'admin', $adminId, [
                'sourceVariantId' => $source?->public_id,
                'destinationVariantId' => $locked->destinationVariant->public_id,
                'quantity' => $locked->quantity,
            ]);

            return $locked->fresh(['destinationReservation', 'destinationVariant', 'orderItem']);
        }, 3);
    }

    private function mergeNote(?string $current, ?string $note): ?string
    {
        $note = trim((string) $note);
        if ($note === '') {
            return $current;
        }

        return trim(($current ? $current."\n" : '').$note);
    }
}
