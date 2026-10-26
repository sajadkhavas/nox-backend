<?php

namespace App\Services\Orders;

use App\Enums\DeliveryMethod;
use App\Enums\InventoryReservationStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderInternalNote;
use App\Models\OrderStatusHistory;
use App\Services\Commerce\CommerceAuditService;
use App\Services\Commerce\InventoryLedgerService;
use App\Services\Commerce\RefundService;
use App\Services\Commerce\ShipmentService;
use App\Services\Notifications\NotificationOutboxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrderLifecycleService
{
    public function __construct(
        private readonly NotificationOutboxService $notifications,
        private readonly InventoryLedgerService $inventory,
        private readonly ShipmentService $shipments,
        private readonly RefundService $refunds,
        private readonly CommerceAuditService $audit,
    ) {}

    public function cancelByCustomer(Order $order, Customer $customer): Order
    {
        return DB::transaction(function () use ($order, $customer): Order {
            $locked = Order::query()->whereKey($order->getKey())->where('customer_id', $customer->getKey())->lockForUpdate()->firstOrFail();
            if (! $locked->canBeCancelledByCustomer()) {
                throw ValidationException::withMessages(['order' => ['این سفارش در وضعیت فعلی قابل لغو نیست.']]);
            }
            $this->releaseReservationsLocked($locked, InventoryReservationStatus::Released, 'customer_cancelled', 'customer', $customer->getKey());
            $this->shipments->cancel($locked, 'customer', $customer->getKey());
            $this->transitionLocked($locked, OrderStatus::Cancelled, 'customer', $customer->getKey(), 'سفارش پیش از پرداخت توسط مشتری لغو شد.');
            $locked->forceFill(['cancelled_at' => now()])->save();
            $this->notifications->queueOrder($locked, 'order.cancelled');
            $this->audit->record('order.cancelled', 'order', $locked->public_id, $locked, 'customer', $customer->getKey());

            return $locked->fresh(['items', 'reservations', 'paymentAttempts', 'statusHistory', 'shipment', 'refunds']);
        }, 3);
    }

    public function transitionByAdmin(Order $order, OrderStatus $target, ?int $actorId, ?string $note = null, ?string $trackingCode = null): Order
    {
        return DB::transaction(function () use ($order, $target, $actorId, $note, $trackingCode): Order {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            if (! in_array($target, $this->allowedTargets($locked->status), true)) {
                throw ValidationException::withMessages(['status' => ['این انتقال وضعیت مجاز نیست.']]);
            }
            if ($target === OrderStatus::Cancelled) {
                $this->cancelByAdminLocked($locked, $actorId, $note);

                return $locked->fresh(['items', 'reservations', 'paymentAttempts', 'statusHistory', 'internalNotes.user', 'shipment', 'refunds']);
            }

            $this->validateDeliveryTransition($locked, $target, $trackingCode);
            $attributes = match ($target) {
                OrderStatus::Confirmed => ['confirmed_at' => now()],
                OrderStatus::Preparing => ['preparing_at' => now()],
                OrderStatus::Ready => ['ready_at' => now()],
                OrderStatus::Dispatched => ['dispatched_at' => now(), 'tracking_code' => trim((string) $trackingCode)],
                OrderStatus::Delivered => ['delivered_at' => now()],
                default => [],
            };
            if ($attributes !== []) {
                $locked->forceFill($attributes)->save();
            }

            match ($target) {
                OrderStatus::Ready => $this->shipments->markReady($locked, 'admin', $actorId),
                OrderStatus::Dispatched => $this->shipments->markShipped($locked, (string) $trackingCode, null, 'admin', $actorId),
                OrderStatus::Delivered => $this->shipments->markDelivered($locked, 'admin', $actorId),
                default => null,
            };
            $this->transitionLocked($locked, $target, 'admin', $actorId, $this->nullableNote($note) ?? "وضعیت سفارش به {$target->label()} تغییر کرد.");

            $templateKey = match ($target) {
                OrderStatus::Preparing => 'order.preparing', OrderStatus::Ready => 'order.ready',
                OrderStatus::Dispatched => 'order.dispatched', OrderStatus::Delivered => 'order.delivered', default => null,
            };
            if ($templateKey !== null) {
                $this->notifications->queueOrder($locked, $templateKey);
            }
            $this->audit->record('order.status_changed', 'order', $locked->public_id, $locked, 'admin', $actorId, ['to' => $target->value]);

            return $locked->fresh(['items', 'reservations', 'paymentAttempts', 'statusHistory', 'internalNotes.user', 'shipment', 'refunds']);
        }, 3);
    }

    public function addInternalNote(Order $order, ?int $actorId, string $note): OrderInternalNote
    {
        $note = trim($note);
        if ($note === '') {
            throw ValidationException::withMessages(['note' => ['یادداشت نمی‌تواند خالی باشد.']]);
        }
        $created = OrderInternalNote::query()->create(['order_id' => $order->getKey(), 'user_id' => $actorId, 'note' => $note]);
        $this->audit->record('order.internal_note_added', 'order', $order->public_id, $order, 'admin', $actorId);

        return $created;
    }

    public function expireAwaitingPaymentOrders(): int
    {
        $ids = Order::query()->where('status', OrderStatus::AwaitingPayment->value)->whereNotNull('reservation_expires_at')
            ->where('reservation_expires_at', '<=', now())->pluck('id');
        $expired = 0;
        foreach ($ids as $id) {
            $didExpire = DB::transaction(function () use ($id): bool {
                $order = Order::query()->whereKey($id)->lockForUpdate()->first();
                if (! $order || $order->status !== OrderStatus::AwaitingPayment || $order->reservation_expires_at?->isFuture()) {
                    return false;
                }
                $this->releaseReservationsLocked($order, InventoryReservationStatus::Expired, 'payment_timeout');
                $this->shipments->cancel($order);
                $this->transitionLocked($order, OrderStatus::Expired, 'system', null, 'مهلت پرداخت و رزرو موجودی پایان یافت.');
                $this->audit->record('order.expired', 'order', $order->public_id, $order);

                return true;
            }, 3);
            if ($didExpire) {
                $expired++;
            }
        }

        return $expired;
    }

    public function consumeReservations(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $this->consumeReservationsLocked($lockedOrder);
        }, 3);
    }

    public function markPaidFromVerifiedPaymentLocked(Order $order): void
    {
        if ($order->status === OrderStatus::Paid && $order->payment_status === PaymentStatus::Paid) {
            return;
        }
        if ($order->status !== OrderStatus::AwaitingPayment) {
            throw ValidationException::withMessages(['order' => ['این سفارش دیگر در وضعیت قابل پرداخت نیست.']]);
        }
        if (! $order->reservation_expires_at || $order->reservation_expires_at->isPast()) {
            throw ValidationException::withMessages(['order' => ['مهلت رزرو موجودی سفارش پایان یافته است.']]);
        }
        $this->consumeReservationsLocked($order);
        $order->forceFill(['payment_status' => PaymentStatus::Paid, 'paid_at' => now()])->save();
        $this->transitionLocked($order, OrderStatus::Paid, 'payment', null, 'پرداخت توسط درگاه تأیید شد و رزرو موجودی به‌صورت اتمیک از Ledger مصرف شد.');
        $this->shipments->ensureForOrder($order);
        $this->notifications->queueOrder($order, 'order.paid');
        $this->audit->record('payment.order_paid', 'order', $order->public_id, $order, 'payment');
    }

    private function consumeReservationsLocked(Order $order): void
    {
        $reservations = InventoryReservation::query()->where('order_id', $order->getKey())
            ->where('status', InventoryReservationStatus::Active->value)->orderBy('variant_id')->lockForUpdate()->get();
        if ($reservations->isEmpty() || $reservations->contains(fn (InventoryReservation $r): bool => ! $r->isActive())) {
            throw ValidationException::withMessages(['order' => ['رزرو موجودی سفارش معتبر نیست یا منقضی شده است.']]);
        }
        foreach ($reservations as $reservation) {
            $this->inventory->consume($reservation);
        }
    }

    private function restockConsumedReservationsLocked(Order $order, ?int $actorId): void
    {
        $reservations = InventoryReservation::query()->where('order_id', $order->getKey())
            ->where('status', InventoryReservationStatus::Consumed->value)->orderBy('variant_id')->lockForUpdate()->get();
        foreach ($reservations as $reservation) {
            $this->inventory->restockConsumed($reservation, 'admin_cancelled_after_payment', 'admin', $actorId);
        }
    }

    private function cancelByAdminLocked(Order $order, ?int $actorId, ?string $note): void
    {
        $wasPaid = in_array($order->payment_status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true);
        if ($order->status === OrderStatus::AwaitingPayment) {
            $this->releaseReservationsLocked($order, InventoryReservationStatus::Released, 'admin_cancelled_before_payment', 'admin', $actorId);
        } else {
            $this->restockConsumedReservationsLocked($order, $actorId);
        }
        $this->shipments->cancel($order, 'admin', $actorId);
        $order->forceFill(['cancelled_at' => now(), 'admin_cancelled_at' => now()])->save();
        $this->transitionLocked($order, OrderStatus::Cancelled, 'admin', $actorId, $this->nullableNote($note) ?? 'سفارش توسط مدیر لغو شد.');
        if ($wasPaid) {
            $this->refunds->requestForCancellation($order, $actorId, $this->nullableNote($note));
        }
        $this->notifications->queueOrder($order, 'order.cancelled');
        $this->audit->record('order.cancelled', 'order', $order->public_id, $order, 'admin', $actorId, ['refundRequired' => $wasPaid]);
    }

    private function releaseReservationsLocked(Order $order, InventoryReservationStatus $status, string $reason, string $actorType = 'system', ?int $actorId = null): void
    {
        $reservations = InventoryReservation::query()->where('order_id', $order->getKey())
            ->where('status', InventoryReservationStatus::Active->value)->orderBy('variant_id')->lockForUpdate()->get();
        foreach ($reservations as $reservation) {
            $this->inventory->release($reservation, $status, $reason, $actorType, $actorId);
        }
    }

    private function transitionLocked(Order $order, OrderStatus $to, string $actorType, ?int $actorId, string $note): void
    {
        $from = $order->status;
        $order->forceFill(['status' => $to])->save();
        OrderStatusHistory::query()->create([
            'order_id' => $order->getKey(), 'from_status' => $from, 'to_status' => $to, 'actor_type' => $actorType,
            'actor_id' => $actorId, 'note' => $note, 'created_at' => now(),
        ]);
    }

    /** @return array<int,OrderStatus> */
    private function allowedTargets(OrderStatus $status): array
    {
        return match ($status) {
            OrderStatus::AwaitingPayment => [OrderStatus::Cancelled],
            OrderStatus::Paid => [OrderStatus::Confirmed, OrderStatus::Cancelled],
            OrderStatus::Confirmed => [OrderStatus::Preparing, OrderStatus::Cancelled],
            OrderStatus::Preparing => [OrderStatus::Ready, OrderStatus::Cancelled],
            OrderStatus::Ready => [OrderStatus::Dispatched, OrderStatus::Delivered, OrderStatus::Cancelled],
            OrderStatus::Dispatched => [OrderStatus::Delivered],
            default => [],
        };
    }

    private function validateDeliveryTransition(Order $order, OrderStatus $target, ?string $trackingCode): void
    {
        if ($target === OrderStatus::Dispatched) {
            if ($order->delivery_method === DeliveryMethod::Pickup) {
                throw ValidationException::withMessages(['status' => ['سفارش تحویل حضوری وارد وضعیت ارسال‌شده نمی‌شود.']]);
            }
            if (trim((string) $trackingCode) === '') {
                throw ValidationException::withMessages(['trackingCode' => ['برای ثبت ارسال، کد پیگیری الزامی است.']]);
            }
        }
        if ($target === OrderStatus::Delivered && $order->status === OrderStatus::Ready && $order->delivery_method !== DeliveryMethod::Pickup) {
            throw ValidationException::withMessages(['status' => ['سفارش ارسالی ابتدا باید وارد وضعیت ارسال‌شده شود.']]);
        }
    }

    private function nullableNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : $note;
    }
}
