<?php

namespace App\Services\Commerce;

use App\Enums\CommerceErrorCode;
use App\Enums\InventoryLedgerEventType;
use App\Enums\InventoryReservationStatus;
use App\Exceptions\CommerceException;
use App\Models\InventoryLedgerEntry;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

final class InventoryLedgerService
{
    public function __construct(private readonly CommerceAuditService $audit) {}

    public function available(ProductVariant|int $variant): int
    {
        $variantId = $variant instanceof ProductVariant ? $variant->getKey() : $variant;
        $onHand = (int) ProductVariant::query()->whereKey($variantId)->value('stock_quantity');
        $reserved = (int) InventoryReservation::query()
            ->where('variant_id', $variantId)
            ->where('status', InventoryReservationStatus::Active->value)
            ->where('expires_at', '>', now())
            ->sum('quantity');

        return max(0, $onHand - $reserved);
    }

    public function reserveForOrder(
        Order $order,
        ProductVariant $variant,
        int $quantity,
        \DateTimeInterface $expiresAt,
        string $purpose = 'checkout',
        ?string $correlationKey = null,
        string $actorType = 'system',
        ?int $actorId = null,
    ): InventoryReservation {
        if ($quantity < 1) {
            throw new CommerceException(
                CommerceErrorCode::InvalidQuantity,
                'تعداد رزرو معتبر نیست.',
                errors: ['quantity' => ['تعداد باید حداقل یک باشد.']],
            );
        }

        return DB::transaction(function () use ($order, $variant, $quantity, $expiresAt, $purpose, $correlationKey, $actorType, $actorId): InventoryReservation {
            $locked = ProductVariant::query()->whereKey($variant->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureOpeningBalanceLocked($locked);
            $this->expireStaleReservationsLocked($locked);

            $reservedBefore = $this->reservedLocked($locked->getKey());
            $available = max(0, (int) $locked->stock_quantity - $reservedBefore);
            if ($quantity > $available) {
                throw new CommerceException(
                    CommerceErrorCode::OutOfStock,
                    'موجودی کافی برای این انتخاب وجود ندارد.',
                    409,
                    ['variantId' => $locked->public_id, 'requested' => $quantity, 'available' => $available],
                );
            }

            $correlationKey ??= "order:{$order->public_id}:variant:{$locked->public_id}:{$purpose}";
            $existing = InventoryReservation::query()->where('correlation_key', $correlationKey)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $reservation = InventoryReservation::query()->create([
                'order_id' => $order->getKey(),
                'variant_id' => $locked->getKey(),
                'purpose' => $purpose,
                'correlation_key' => $correlationKey,
                'quantity' => $quantity,
                'status' => InventoryReservationStatus::Active,
                'expires_at' => $expiresAt,
            ]);

            $reservedAfter = $reservedBefore + $quantity;
            $this->recordLocked(
                $locked,
                InventoryLedgerEventType::ReservationCreated,
                0,
                $quantity,
                $reservedAfter,
                $order,
                $reservation,
                $purpose,
                $reservation->public_id,
                'inventory_reserved',
                $actorType,
                $actorId,
                "reservation:create:{$reservation->public_id}",
            );
            $this->audit->record('inventory.reserved', 'inventory_reservation', $reservation->public_id, $order, $actorType, $actorId, [
                'variantPublicId' => $locked->public_id,
                'quantity' => $quantity,
                'purpose' => $purpose,
            ]);

            return $reservation;
        }, 3);
    }

    public function consume(InventoryReservation $reservation, string $actorType = 'payment', ?int $actorId = null): InventoryReservation
    {
        return DB::transaction(function () use ($reservation, $actorType, $actorId): InventoryReservation {
            $lockedReservation = InventoryReservation::query()->whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();
            $variant = ProductVariant::query()->whereKey($lockedReservation->variant_id)->lockForUpdate()->firstOrFail();
            $this->ensureOpeningBalanceLocked($variant);
            $this->expireStaleReservationsLocked($variant);
            $lockedReservation->refresh();

            $idempotencyKey = "reservation:consume:{$lockedReservation->public_id}";
            if (InventoryLedgerEntry::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                return $lockedReservation;
            }

            if ($lockedReservation->status !== InventoryReservationStatus::Active || $lockedReservation->expires_at->isPast()) {
                throw new CommerceException(
                    CommerceErrorCode::ReservationExpired,
                    'رزرو موجودی معتبر نیست یا منقضی شده است.',
                    409,
                    ['reservationId' => $lockedReservation->public_id],
                );
            }

            if ((int) $variant->stock_quantity < (int) $lockedReservation->quantity) {
                throw new CommerceException(
                    CommerceErrorCode::OutOfStock,
                    'موجودی فیزیکی برای تکمیل عملیات کافی نیست.',
                    409,
                    ['variantId' => $variant->public_id],
                );
            }

            $reservedBefore = $this->reservedLocked($variant->getKey());
            $this->saveStockLocked($variant, (int) $variant->stock_quantity - (int) $lockedReservation->quantity);
            $lockedReservation->forceFill([
                'status' => InventoryReservationStatus::Consumed,
                'consumed_at' => now(),
            ])->save();
            $reservedAfter = max(0, $reservedBefore - (int) $lockedReservation->quantity);

            $this->recordLocked(
                $variant,
                $lockedReservation->purpose === 'exchange' ? InventoryLedgerEventType::ExchangeOut : InventoryLedgerEventType::Sale,
                -((int) $lockedReservation->quantity),
                -((int) $lockedReservation->quantity),
                $reservedAfter,
                $lockedReservation->order,
                $lockedReservation,
                $lockedReservation->purpose,
                $lockedReservation->public_id,
                'reservation_consumed',
                $actorType,
                $actorId,
                $idempotencyKey,
            );

            return $lockedReservation;
        }, 3);
    }

    public function release(
        InventoryReservation $reservation,
        InventoryReservationStatus $target = InventoryReservationStatus::Released,
        string $reason = 'released',
        string $actorType = 'system',
        ?int $actorId = null,
    ): InventoryReservation {
        return DB::transaction(function () use ($reservation, $target, $reason, $actorType, $actorId): InventoryReservation {
            $lockedReservation = InventoryReservation::query()->whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();
            $variant = ProductVariant::query()->whereKey($lockedReservation->variant_id)->lockForUpdate()->firstOrFail();
            $this->ensureOpeningBalanceLocked($variant);

            if ($lockedReservation->status !== InventoryReservationStatus::Active) {
                return $lockedReservation;
            }

            $reservedBefore = $this->reservedLocked($variant->getKey());
            $lockedReservation->forceFill([
                'status' => $target,
                'released_at' => now(),
                'release_reason' => $reason,
            ])->save();
            $reservedAfter = max(0, $reservedBefore - (int) $lockedReservation->quantity);
            $event = $target === InventoryReservationStatus::Expired
                ? InventoryLedgerEventType::ReservationExpired
                : InventoryLedgerEventType::ReservationReleased;

            $this->recordLocked(
                $variant,
                $event,
                0,
                -((int) $lockedReservation->quantity),
                $reservedAfter,
                $lockedReservation->order,
                $lockedReservation,
                $lockedReservation->purpose,
                $lockedReservation->public_id,
                $reason,
                $actorType,
                $actorId,
                "reservation:release:{$target->value}:{$lockedReservation->public_id}",
            );

            return $lockedReservation;
        }, 3);
    }

    public function restockConsumed(
        InventoryReservation $reservation,
        string $reason,
        string $actorType = 'system',
        ?int $actorId = null,
        InventoryLedgerEventType $event = InventoryLedgerEventType::Return,
    ): InventoryReservation {
        return DB::transaction(function () use ($reservation, $reason, $actorType, $actorId, $event): InventoryReservation {
            $lockedReservation = InventoryReservation::query()->whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();
            $variant = ProductVariant::query()->whereKey($lockedReservation->variant_id)->lockForUpdate()->firstOrFail();
            $this->ensureOpeningBalanceLocked($variant);

            $idempotencyKey = "reservation:restock:{$lockedReservation->public_id}";
            if (InventoryLedgerEntry::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                return $lockedReservation;
            }
            if ($lockedReservation->status !== InventoryReservationStatus::Consumed) {
                return $lockedReservation;
            }

            $this->saveStockLocked($variant, (int) $variant->stock_quantity + (int) $lockedReservation->quantity);
            $lockedReservation->forceFill([
                'status' => InventoryReservationStatus::Restocked,
                'restocked_at' => now(),
                'released_at' => now(),
                'release_reason' => $reason,
            ])->save();
            $reserved = $this->reservedLocked($variant->getKey());
            $this->recordLocked(
                $variant,
                $event,
                (int) $lockedReservation->quantity,
                0,
                $reserved,
                $lockedReservation->order,
                $lockedReservation,
                $lockedReservation->purpose,
                $lockedReservation->public_id,
                $reason,
                $actorType,
                $actorId,
                $idempotencyKey,
            );

            return $lockedReservation;
        }, 3);
    }

    public function adjust(
        ProductVariant $variant,
        int $delta,
        string $reason,
        string $idempotencyKey,
        string $actorType = 'admin',
        ?int $actorId = null,
    ): ProductVariant {
        return DB::transaction(function () use ($variant, $delta, $reason, $idempotencyKey, $actorType, $actorId): ProductVariant {
            $locked = ProductVariant::query()->whereKey($variant->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureOpeningBalanceLocked($locked);
            $this->expireStaleReservationsLocked($locked);

            if (InventoryLedgerEntry::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                return $locked;
            }

            $reserved = $this->reservedLocked($locked->getKey());
            $newOnHand = (int) $locked->stock_quantity + $delta;
            if ($newOnHand < 0 || $newOnHand < $reserved) {
                throw new CommerceException(
                    CommerceErrorCode::OutOfStock,
                    'اصلاح موجودی با رزروهای فعال سازگار نیست.',
                    409,
                    ['onHand' => (int) $locked->stock_quantity, 'reserved' => $reserved, 'delta' => $delta],
                );
            }

            $this->saveStockLocked($locked, $newOnHand);
            $this->recordLocked(
                $locked,
                InventoryLedgerEventType::ManualCorrection,
                $delta,
                0,
                $reserved,
                null,
                null,
                'inventory_adjustment',
                $locked->public_id,
                $reason,
                $actorType,
                $actorId,
                $idempotencyKey,
            );
            $this->audit->record('inventory.adjusted', 'product_variant', $locked->public_id, null, $actorType, $actorId, [
                'delta' => $delta,
                'reason' => $reason,
                'onHandAfter' => $newOnHand,
                'reservedAfter' => $reserved,
            ]);

            return $locked;
        }, 3);
    }

    public function recordReturnStock(
        ProductVariant $variant,
        int $quantity,
        string $correlationType,
        string $correlationPublicId,
        ?Order $order,
        string $reason,
        string $idempotencyKey,
        string $actorType = 'admin',
        ?int $actorId = null,
        InventoryLedgerEventType $event = InventoryLedgerEventType::Return,
    ): ProductVariant {
        return DB::transaction(function () use ($variant, $quantity, $correlationType, $correlationPublicId, $order, $reason, $idempotencyKey, $actorType, $actorId, $event): ProductVariant {
            $locked = ProductVariant::query()->whereKey($variant->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureOpeningBalanceLocked($locked);
            if (InventoryLedgerEntry::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                return $locked;
            }

            $this->saveStockLocked($locked, (int) $locked->stock_quantity + $quantity);
            $reserved = $this->reservedLocked($locked->getKey());
            $this->recordLocked(
                $locked,
                $event,
                $quantity,
                0,
                $reserved,
                $order,
                null,
                $correlationType,
                $correlationPublicId,
                $reason,
                $actorType,
                $actorId,
                $idempotencyKey,
            );

            return $locked;
        }, 3);
    }

    public function expireStaleReservationsForVariant(ProductVariant $variant): int
    {
        return DB::transaction(function () use ($variant): int {
            $locked = ProductVariant::query()->whereKey($variant->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureOpeningBalanceLocked($locked);

            return $this->expireStaleReservationsLocked($locked);
        }, 3);
    }

    private function expireStaleReservationsLocked(ProductVariant $variant): int
    {
        $reservations = InventoryReservation::query()
            ->where('variant_id', $variant->getKey())
            ->where('status', InventoryReservationStatus::Active->value)
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $count = 0;
        foreach ($reservations as $reservation) {
            $reservedBefore = $this->reservedLocked($variant->getKey());
            $reservation->forceFill([
                'status' => InventoryReservationStatus::Expired,
                'released_at' => now(),
                'release_reason' => 'reservation_timeout',
            ])->save();
            $reservedAfter = max(0, $reservedBefore - (int) $reservation->quantity);
            $this->recordLocked(
                $variant,
                InventoryLedgerEventType::ReservationExpired,
                0,
                -((int) $reservation->quantity),
                $reservedAfter,
                $reservation->order,
                $reservation,
                $reservation->purpose,
                $reservation->public_id,
                'reservation_timeout',
                'system',
                null,
                "reservation:expire:{$reservation->public_id}",
            );
            $count++;
        }

        return $count;
    }

    private function ensureOpeningBalanceLocked(ProductVariant $variant): void
    {
        if (InventoryLedgerEntry::query()->where('variant_id', $variant->getKey())->exists()) {
            return;
        }

        $reserved = $this->reservedLocked($variant->getKey());
        InventoryLedgerEntry::query()->create([
            'variant_id' => $variant->getKey(),
            'event_type' => InventoryLedgerEventType::OpeningBalance,
            'on_hand_delta' => (int) $variant->stock_quantity,
            'reserved_delta' => $reserved,
            'on_hand_after' => (int) $variant->stock_quantity,
            'reserved_after' => $reserved,
            'available_after' => max(0, (int) $variant->stock_quantity - $reserved),
            'correlation_type' => 'variant',
            'correlation_public_id' => $variant->public_id,
            'reason' => 'ledger_opening_balance',
            'actor_type' => 'system',
            'idempotency_key' => "inventory:opening:{$variant->public_id}",
            'created_at' => now(),
        ]);
    }

    private function reservedLocked(int $variantId): int
    {
        return (int) InventoryReservation::query()
            ->where('variant_id', $variantId)
            ->where('status', InventoryReservationStatus::Active->value)
            ->sum('quantity');
    }

    private function recordLocked(
        ProductVariant $variant,
        InventoryLedgerEventType $event,
        int $onHandDelta,
        int $reservedDelta,
        int $reservedAfter,
        ?Order $order,
        ?InventoryReservation $reservation,
        ?string $correlationType,
        ?string $correlationPublicId,
        ?string $reason,
        string $actorType,
        ?int $actorId,
        ?string $idempotencyKey,
        array $metadata = [],
    ): InventoryLedgerEntry {
        $onHand = (int) $variant->fresh()->stock_quantity;

        return InventoryLedgerEntry::query()->create([
            'variant_id' => $variant->getKey(),
            'order_id' => $order?->getKey(),
            'reservation_id' => $reservation?->getKey(),
            'event_type' => $event,
            'on_hand_delta' => $onHandDelta,
            'reserved_delta' => $reservedDelta,
            'on_hand_after' => $onHand,
            'reserved_after' => max(0, $reservedAfter),
            'available_after' => max(0, $onHand - max(0, $reservedAfter)),
            'correlation_type' => $correlationType,
            'correlation_public_id' => $correlationPublicId,
            'reason' => $reason,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'idempotency_key' => $idempotencyKey,
            'metadata' => $metadata === [] ? null : $metadata,
            'created_at' => now(),
        ]);
    }

    private function saveStockLocked(ProductVariant $variant, int $quantity): void
    {
        app()->instance('lbb.inventory_ledger_mutation', true);
        try {
            $variant->forceFill(['stock_quantity' => $quantity])->save();
        } finally {
            app()->forgetInstance('lbb.inventory_ledger_mutation');
        }
    }
}
