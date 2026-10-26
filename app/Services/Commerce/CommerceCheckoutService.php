<?php

namespace App\Services\Commerce;

use App\Enums\CheckoutQuoteStatus;
use App\Enums\CommerceErrorCode;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\CommerceException;
use App\Exceptions\IdempotencyConflict;
use App\Models\CheckoutQuote;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CommerceCheckoutService
{
    public function __construct(
        private readonly CartValidationService $cart,
        private readonly InventoryLedgerService $inventory,
        private readonly ShipmentService $shipments,
        private readonly CommerceAuditService $audit,
    ) {}

    /** @return array{order: Order,replayed: bool} */
    public function commit(Customer $customer, string $quotePublicId, string $idempotencyKey): array
    {
        $requestHash = hash('sha256', $customer->public_id.'|'.$quotePublicId);
        $existing = $this->existing($customer, $idempotencyKey);
        if ($existing) {
            return $this->replay($existing, $requestHash);
        }

        try {
            return DB::transaction(function () use ($customer, $quotePublicId, $idempotencyKey, $requestHash): array {
                $existing = Order::query()
                    ->ownedBy($customer)
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();
                if ($existing) {
                    return $this->replay($existing, $requestHash);
                }

                $quote = CheckoutQuote::query()
                    ->where('customer_id', $customer->getKey())
                    ->where('public_id', $quotePublicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($quote->status === CheckoutQuoteStatus::Consumed && $quote->consumed_order_public_id) {
                    $order = Order::query()
                        ->ownedBy($customer)
                        ->where('public_id', $quote->consumed_order_public_id)
                        ->first();
                    if ($order) {
                        return ['order' => $this->load($order), 'replayed' => true];
                    }
                }
                if ($quote->status !== CheckoutQuoteStatus::Active || $quote->expires_at->isPast()) {
                    $quote->forceFill(['status' => CheckoutQuoteStatus::Expired])->save();
                    throw new CommerceException(
                        CommerceErrorCode::QuoteExpired,
                        'اعتبار Quote پایان یافته است؛ Quote جدید دریافت کنید.',
                        409,
                        ['quoteId' => $quote->public_id],
                    );
                }

                $payload = [
                    'customer' => $quote->recipient_snapshot,
                    'deliveryMethod' => $quote->delivery_method,
                    'items' => collect($quote->items_snapshot)->map(fn (array $item): array => [
                        'variantId' => $item['variantId'],
                        'quantity' => (int) $item['quantity'],
                        'expectedUnitPriceToman' => (int) $item['unitPriceToman'],
                    ])->all(),
                ];
                $current = $this->cart->validate($customer, $payload, true);
                $this->assertQuoteStillCurrent($quote, $current);

                $variantMap = ProductVariant::query()
                    ->whereIn('public_id', collect($current['items'])->pluck('variantId'))
                    ->with(['product', 'color', 'size'])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('public_id');

                $expiresAt = now()->addMinutes(max(1, (int) config('lbb.checkout.reservation_minutes', 20)));
                $recipient = $current['recipient'];
                $order = Order::query()->create([
                    'customer_id' => $customer->getKey(),
                    'checkout_quote_id' => $quote->getKey(),
                    'order_number' => $this->nextOrderNumber(),
                    'idempotency_key' => $idempotencyKey,
                    'request_hash' => $requestHash,
                    'status' => OrderStatus::AwaitingPayment,
                    'payment_status' => PaymentStatus::Unpaid,
                    'delivery_method' => $current['deliveryMethod'],
                    'delivery_zone_id' => $current['deliveryZoneId'],
                    'subtotal_toman' => $current['subtotalToman'],
                    'delivery_fee_toman' => $current['deliveryFeeToman'],
                    'packaging_fee_toman' => $current['packagingFeeToman'],
                    'discount_total_toman' => $current['discountTotalToman'],
                    'grand_total_toman' => $current['grandTotalToman'],
                    'currency' => $current['currency'],
                    'item_count' => collect($current['items'])->sum('quantity'),
                    'preparation_time_days' => $current['preparationMinDays'],
                    'preparation_max_days' => $current['preparationMaxDays'],
                    'customer_name' => $recipient['fullName'],
                    'customer_mobile' => $recipient['mobile'],
                    'province' => $recipient['province'] ?: null,
                    'city' => $recipient['city'] ?: null,
                    'address' => $recipient['address'] ?: null,
                    'postal_code' => $recipient['postalCode'] ?: null,
                    'notes' => $recipient['notes'] ?: null,
                    'reservation_expires_at' => $expiresAt,
                    'placed_at' => now(),
                ]);

                foreach ($current['items'] as $item) {
                    /** @var ProductVariant $variant */
                    $variant = $variantMap->get($item['variantId']);
                    $order->items()->create([
                        'product_id' => $variant->product_id,
                        'variant_id' => $variant->getKey(),
                        'product_public_id' => $item['productId'],
                        'variant_public_id' => $item['variantId'],
                        'product_name' => $item['productName'],
                        'variant_name' => $item['variantName'],
                        'product_code' => $item['productCode'],
                        'sku' => $item['sku'],
                        'color_name' => $item['colorName'],
                        'size_name' => $item['sizeName'],
                        'unit_price_toman' => $item['unitPriceToman'],
                        'quantity' => $item['quantity'],
                        'line_total_toman' => $item['lineTotalToman'],
                        'currency' => $current['currency'],
                    ]);
                    $this->inventory->reserveForOrder(
                        $order,
                        $variant,
                        (int) $item['quantity'],
                        $expiresAt,
                        'checkout',
                        "checkout:{$order->public_id}:{$variant->public_id}",
                        'customer',
                        $customer->getKey(),
                    );
                }

                OrderStatusHistory::query()->create([
                    'order_id' => $order->getKey(),
                    'from_status' => null,
                    'to_status' => OrderStatus::AwaitingPayment,
                    'actor_type' => 'customer',
                    'actor_id' => $customer->getKey(),
                    'note' => 'Checkout Commit اتمیک انجام و موجودی از مسیر Ledger رزرو شد.',
                    'created_at' => now(),
                ]);
                $this->shipments->ensureForOrder($order);

                $quote->forceFill([
                    'status' => CheckoutQuoteStatus::Consumed,
                    'consumed_at' => now(),
                    'consumed_order_public_id' => $order->public_id,
                ])->save();
                $this->audit->record('checkout.committed', 'order', $order->public_id, $order, 'customer', $customer->getKey(), [
                    'quotePublicId' => $quote->public_id,
                    'grandTotalToman' => $order->grand_total_toman,
                    'reservationExpiresAt' => $expiresAt->toIso8601String(),
                ]);

                return ['order' => $this->load($order), 'replayed' => false];
            }, 3);
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw $exception;
            }
            $existing = $this->existing($customer, $idempotencyKey);
            if (! $existing) {
                throw $exception;
            }

            return $this->replay($existing, $requestHash);
        }
    }

    /** @param array<string,mixed> $current */
    private function assertQuoteStillCurrent(CheckoutQuote $quote, array $current): void
    {
        $totalsChanged = (int) $quote->subtotal_toman !== (int) $current['subtotalToman']
            || (int) $quote->delivery_fee_toman !== (int) $current['deliveryFeeToman']
            || (int) $quote->packaging_fee_toman !== (int) $current['packagingFeeToman']
            || (int) $quote->discount_total_toman !== (int) $current['discountTotalToman']
            || (int) $quote->grand_total_toman !== (int) $current['grandTotalToman'];

        if ($totalsChanged) {
            throw new CommerceException(
                CommerceErrorCode::PriceChanged,
                'مبالغ Quote تغییر کرده‌اند؛ Quote جدید دریافت کنید.',
                409,
                [
                    'quoteId' => $quote->public_id,
                    'current' => [
                        'subtotalToman' => $current['subtotalToman'],
                        'deliveryFeeToman' => $current['deliveryFeeToman'],
                        'packagingFeeToman' => $current['packagingFeeToman'],
                        'grandTotalToman' => $current['grandTotalToman'],
                    ],
                ],
            );
        }
    }

    private function existing(Customer $customer, string $key): ?Order
    {
        return Order::query()->ownedBy($customer)->where('idempotency_key', $key)->first();
    }

    /** @return array{order: Order,replayed: bool} */
    private function replay(Order $order, string $requestHash): array
    {
        if (! hash_equals((string) $order->request_hash, $requestHash)) {
            throw new IdempotencyConflict;
        }

        return ['order' => $this->load($order), 'replayed' => true];
    }

    private function load(Order $order): Order
    {
        return $order->load(['items', 'reservations', 'deliveryZone', 'shipment', 'paymentAttempts']);
    }

    private function nextOrderNumber(): string
    {
        return 'LBB-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
    }
}
