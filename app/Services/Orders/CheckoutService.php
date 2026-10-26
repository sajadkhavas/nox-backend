<?php

namespace App\Services\Orders;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\IdempotencyConflict;
use App\Exceptions\InventoryUnavailable;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Services\Commerce\InventoryLedgerService;
use App\Services\Commerce\ShipmentService;
use App\Services\Store\DeliveryConfigurationService;
use App\Support\IranianMobile;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;

final class CheckoutService
{
    public function __construct(
        private readonly DeliveryConfigurationService $delivery,
        private readonly InventoryLedgerService $inventory,
        private readonly ShipmentService $shipments,
    ) {}

    /** @return array{order: Order, replayed: bool} @throws JsonException */
    public function create(Customer $customer, array $payload, string $idempotencyKey): array
    {
        $payload = $this->resolveCustomerPayload($customer, $payload);
        $canonical = $this->canonicalize($payload);
        $requestHash = hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $existing = $this->findExisting($customer, $idempotencyKey);
        if ($existing) {
            return $this->replay($existing, $requestHash);
        }

        try {
            return DB::transaction(function () use ($customer, $canonical, $idempotencyKey, $requestHash): array {
                $existing = Order::query()->ownedBy($customer)->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                if ($existing) {
                    return $this->replay($existing, $requestHash);
                }

                return ['order' => $this->createLocked($customer, $canonical, $idempotencyKey, $requestHash), 'replayed' => false];
            }, 3);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }
            $existing = $this->findExisting($customer, $idempotencyKey);
            if (! $existing) {
                throw $exception;
            }

            return $this->replay($existing, $requestHash);
        }
    }

    private function createLocked(Customer $customer, array $payload, string $idempotencyKey, string $requestHash): Order
    {
        $items = collect($payload['items']);
        $variantIds = $items->pluck('variantId')->sort()->values();
        $variants = ProductVariant::query()
            ->whereIn('public_id', $variantIds)->where('is_active', true)
            ->with(['product.category', 'color', 'size'])->orderBy('id')->lockForUpdate()->get()->keyBy('public_id');
        if ($variants->count() !== $variantIds->count()) {
            throw ValidationException::withMessages(['items' => ['یک یا چند محصول دیگر قابل سفارش نیستند.']]);
        }

        $deliveryMethod = DeliveryMethod::from($payload['deliveryMethod']);
        if ($deliveryMethod->requiresAddress()) {
            foreach (['province', 'city', 'address'] as $field) {
                if (trim((string) ($payload['customer'][$field] ?? '')) === '') {
                    throw ValidationException::withMessages(["customer.{$field}" => ['این مقدار برای ارسال سفارش الزامی است.']]);
                }
            }
        }

        $itemSnapshots = [];
        $subtotal = 0;
        foreach ($items as $item) {
            /** @var ProductVariant $variant */
            $variant = $variants->get($item['variantId']);
            $product = $variant->product;
            if (! $product || ! $product->is_active || ! $product->category?->is_active) {
                throw ValidationException::withMessages(['items' => ['یک یا چند محصول دیگر قابل سفارش نیستند.']]);
            }
            $reserved = (int) InventoryReservation::query()->where('variant_id', $variant->getKey())->active()->sum('quantity');
            $available = max(0, (int) $variant->stock_quantity - $reserved);
            $quantity = (int) $item['quantity'];
            if ($quantity > $available) {
                throw new InventoryUnavailable($variant->public_id, $variant->name, $quantity, $available);
            }

            $unitPrice = $variant->current_price_toman;
            $lineTotal = $unitPrice * $quantity;
            $subtotal += $lineTotal;
            $itemSnapshots[] = [
                'product_id' => $product->getKey(), 'variant_id' => $variant->getKey(),
                'product_public_id' => $product->public_id, 'variant_public_id' => $variant->public_id,
                'product_name' => $product->name, 'variant_name' => $variant->name, 'product_code' => $product->product_code,
                'sku' => $variant->sku, 'color_name' => $variant->color?->name, 'size_name' => $variant->size?->name,
                'unit_price_toman' => $unitPrice, 'quantity' => $quantity, 'line_total_toman' => $lineTotal, 'currency' => 'TOMAN',
            ];
        }

        $quote = $this->delivery->quote($deliveryMethod, $payload['customer']['province'] ?? null, $payload['customer']['city'] ?? null, $subtotal);
        $processingMinDays = (int) $quote['preparation_min_days'];
        $processingMaxDays = max($processingMinDays, (int) $quote['preparation_max_days']);
        $reservationExpiresAt = now()->addMinutes(max(1, (int) config('lbb.checkout.reservation_minutes', 20)));
        $order = Order::query()->create([
            'customer_id' => $customer->getKey(), 'order_number' => $this->nextOrderNumber(), 'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash, 'status' => OrderStatus::AwaitingPayment, 'payment_status' => PaymentStatus::Unpaid,
            'delivery_method' => $deliveryMethod, 'delivery_zone_id' => $quote['zone']?->getKey(), 'subtotal_toman' => $subtotal,
            'delivery_fee_toman' => $quote['fee_toman'], 'packaging_fee_toman' => $quote['packaging_fee_toman'],
            'discount_total_toman' => 0, 'grand_total_toman' => $subtotal + $quote['fee_toman'] + $quote['packaging_fee_toman'],
            'currency' => 'TOMAN', 'item_count' => $items->sum('quantity'), 'preparation_time_days' => $processingMinDays,
            'preparation_max_days' => $processingMaxDays, 'customer_name' => trim($payload['customer']['fullName']),
            'customer_mobile' => IranianMobile::normalize($payload['customer']['mobile']),
            'province' => $deliveryMethod->requiresAddress() ? trim($payload['customer']['province']) : null,
            'city' => $deliveryMethod->requiresAddress() ? trim($payload['customer']['city']) : null,
            'address' => $deliveryMethod->requiresAddress() ? trim($payload['customer']['address']) : null,
            'postal_code' => $deliveryMethod->requiresAddress() ? $this->nullableTrim($payload['customer']['postalCode'] ?? null) : null,
            'notes' => $this->nullableTrim($payload['customer']['notes'] ?? null), 'reservation_expires_at' => $reservationExpiresAt,
            'placed_at' => now(),
        ]);
        $order->items()->createMany($itemSnapshots);
        foreach ($itemSnapshots as $snapshot) {
            /** @var ProductVariant $variant */
            $variant = $variants->get($snapshot['variant_public_id']);
            $this->inventory->reserveForOrder(
                $order, $variant, (int) $snapshot['quantity'], $reservationExpiresAt, 'checkout',
                "legacy-checkout:{$order->public_id}:{$variant->public_id}", 'customer', $customer->getKey(),
            );
        }
        $this->shipments->ensureForOrder($order);
        OrderStatusHistory::query()->create([
            'order_id' => $order->getKey(), 'from_status' => null, 'to_status' => OrderStatus::AwaitingPayment,
            'actor_type' => 'customer', 'actor_id' => $customer->getKey(),
            'note' => 'سفارش از Checkout ثبت شد و موجودی از مسیر Ledger رزرو شد.', 'created_at' => now(),
        ]);

        return $this->loadOrder($order);
    }

    private function resolveCustomerPayload(Customer $customer, array $payload): array
    {
        $addressId = trim((string) ($payload['addressId'] ?? ''));
        if ($addressId === '') {
            return $payload;
        }
        $address = CustomerAddress::query()->ownedBy($customer)->where('public_id', $addressId)->where('is_active', true)->first();
        if (! $address) {
            throw ValidationException::withMessages(['addressId' => ['آدرس انتخاب‌شده معتبر یا متعلق به این حساب نیست.']]);
        }
        $payload['customer'] = [
            'fullName' => $address->recipient_name, 'mobile' => $address->mobile, 'province' => $address->province,
            'city' => $address->city, 'address' => $address->address_line, 'postalCode' => $address->postal_code,
            'notes' => $payload['customer']['notes'] ?? null,
        ];

        return $payload;
    }

    private function canonicalize(array $payload): array
    {
        $items = collect($payload['items'])->map(fn (array $item): array => ['variantId' => trim($item['variantId']), 'quantity' => (int) $item['quantity']])
            ->groupBy('variantId')->map(fn (Collection $group, string $variantId): array => ['variantId' => $variantId, 'quantity' => $group->sum('quantity')])
            ->sortBy('variantId')->values()->all();

        return [
            'customer' => [
                'fullName' => trim($payload['customer']['fullName']), 'mobile' => IranianMobile::normalize($payload['customer']['mobile']),
                'province' => trim((string) ($payload['customer']['province'] ?? '')), 'city' => trim((string) ($payload['customer']['city'] ?? '')),
                'address' => trim((string) ($payload['customer']['address'] ?? '')), 'postalCode' => trim((string) ($payload['customer']['postalCode'] ?? '')),
                'notes' => trim((string) ($payload['customer']['notes'] ?? '')),
            ], 'deliveryMethod' => $payload['deliveryMethod'], 'items' => $items,
        ];
    }

    private function findExisting(Customer $customer, string $idempotencyKey): ?Order
    {
        return Order::query()->ownedBy($customer)->where('idempotency_key', $idempotencyKey)->first();
    }

    private function replay(Order $order, string $requestHash): array
    {
        if (! hash_equals($order->request_hash, $requestHash)) {
            throw new IdempotencyConflict;
        }

        return ['order' => $this->loadOrder($order), 'replayed' => true];
    }

    private function loadOrder(Order $order): Order
    {
        return $order->load(['items', 'reservations', 'deliveryZone', 'shipment']);
    }

    private function nextOrderNumber(): string
    {
        return 'LBB-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true);
    }
}
