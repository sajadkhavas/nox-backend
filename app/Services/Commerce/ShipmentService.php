<?php

namespace App\Services\Commerce;

use App\Enums\DeliveryMethod;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Validation\ValidationException;

final class ShipmentService
{
    public function __construct(private readonly CommerceAuditService $audit) {}

    public function ensureForOrder(Order $order): Shipment
    {
        $status = $order->delivery_method === DeliveryMethod::Pickup
            ? ShipmentStatus::NotRequired
            : ShipmentStatus::Pending;

        return Shipment::query()->firstOrCreate(
            ['order_id' => $order->getKey()],
            ['method_snapshot' => $order->delivery_method->value, 'status' => $status],
        );
    }

    public function markReady(Order $order, string $actorType = 'admin', ?int $actorId = null): Shipment
    {
        $shipment = $this->ensureForOrder($order);
        if ($shipment->status === ShipmentStatus::NotRequired) {
            return $shipment;
        }
        $shipment->forceFill(['status' => ShipmentStatus::Ready, 'ready_at' => now()])->save();
        $this->audit->record('shipment.ready', 'shipment', $shipment->public_id, $order, $actorType, $actorId);

        return $shipment;
    }

    public function markShipped(Order $order, string $trackingReference, ?string $carrier = null, string $actorType = 'admin', ?int $actorId = null): Shipment
    {
        $shipment = $this->ensureForOrder($order);
        if ($shipment->status === ShipmentStatus::NotRequired) {
            throw ValidationException::withMessages(['shipment' => ['برای تحویل حضوری Shipment قابل ارسال وجود ندارد.']]);
        }
        $trackingReference = trim($trackingReference);
        if ($trackingReference === '') {
            throw ValidationException::withMessages(['trackingCode' => ['کد پیگیری الزامی است.']]);
        }
        $shipment->forceFill([
            'status' => ShipmentStatus::Shipped,
            'tracking_reference' => $trackingReference,
            'carrier' => $this->nullableTrim($carrier),
            'shipped_at' => now(),
        ])->save();
        $this->audit->record('shipment.shipped', 'shipment', $shipment->public_id, $order, $actorType, $actorId, [
            'trackingReference' => $trackingReference,
            'carrier' => $shipment->carrier,
        ]);

        return $shipment;
    }

    public function markDelivered(Order $order, string $actorType = 'admin', ?int $actorId = null): Shipment
    {
        $shipment = $this->ensureForOrder($order);
        if ($shipment->status !== ShipmentStatus::NotRequired && $shipment->status !== ShipmentStatus::Shipped) {
            throw ValidationException::withMessages(['shipment' => ['Shipment قبل از تحویل باید ارسال شده باشد.']]);
        }
        $shipment->forceFill(['status' => ShipmentStatus::Delivered, 'delivered_at' => now()])->save();
        $this->audit->record('shipment.delivered', 'shipment', $shipment->public_id, $order, $actorType, $actorId);

        return $shipment;
    }

    public function cancel(Order $order, string $actorType = 'system', ?int $actorId = null): Shipment
    {
        $shipment = $this->ensureForOrder($order);
        if ($shipment->status === ShipmentStatus::Delivered) {
            throw ValidationException::withMessages(['shipment' => ['Shipment تحویل‌شده قابل لغو نیست.']]);
        }
        $shipment->forceFill(['status' => ShipmentStatus::Cancelled, 'cancelled_at' => now()])->save();
        $this->audit->record('shipment.cancelled', 'shipment', $shipment->public_id, $order, $actorType, $actorId);

        return $shipment;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
