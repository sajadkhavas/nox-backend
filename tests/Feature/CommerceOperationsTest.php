<?php

namespace Tests\Feature;

use App\Enums\ExchangeStatus;
use App\Enums\InventoryLedgerEventType;
use App\Enums\InventoryReservationStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublicationStatus;
use App\Enums\RefundStatus;
use App\Enums\ReturnResolution;
use App\Enums\ReturnStatus;
use App\Exceptions\CommerceException;
use App\Models\Category;
use App\Models\Color;
use App\Models\Customer;
use App\Models\ExchangeRequest;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RefundRequest;
use App\Models\ReturnRequest;
use App\Models\Size;
use App\Services\Commerce\ExchangeService;
use App\Services\Commerce\InventoryLedgerService;
use App\Services\Commerce\RefundService;
use App\Services\Commerce\ReturnService;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommerceOperationsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Product $product;

    private ProductVariant $source;

    private ProductVariant $destination;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'session.driver' => 'array', 'lbb.checkout.enabled' => true, 'lbb.checkout.reservation_minutes' => 20,
            'lbb.checkout.max_quantity_per_line' => 20, 'lbb.checkout.max_total_units' => 50,
            'lbb.checkout.packaging_fee_toman' => 10_000,
            'lbb.checkout.delivery_methods.standard' => ['enabled' => true, 'fee_toman' => 30_000],
            'lbb.checkout.delivery_methods.pickup' => ['enabled' => true, 'fee_toman' => 0],
            'lbb.payment.enabled' => false, 'lbb.payment.provider' => 'disabled', 'lbb.payment.refunds_enabled' => false,
            'lbb.notifications.sms_provider' => 'disabled',
        ]);
        $this->customer = Customer::query()->create([
            'mobile' => '09121111111', 'full_name' => 'مشتری Commerce', 'mobile_verified_at' => now(), 'is_active' => true,
        ]);
        $category = Category::query()->create([
            'name' => 'لباس', 'slug' => 'commerce-apparel', 'publication_status' => PublicationStatus::Published, 'is_active' => true,
        ]);
        $this->product = Product::query()->create([
            'category_id' => $category->getKey(), 'name' => 'تی‌شرت Commerce', 'slug' => 'commerce-shirt',
            'product_code' => 'COM-SHIRT', 'publication_status' => PublicationStatus::Draft, 'is_active' => true,
        ]);
        $black = Color::query()->create(['name' => 'مشکی', 'slug' => 'commerce-black', 'code' => 'BLACK', 'is_active' => true]);
        $red = Color::query()->create(['name' => 'قرمز', 'slug' => 'commerce-red', 'code' => 'RED', 'is_active' => true]);
        $medium = Size::query()->create(['name' => 'M', 'code' => 'M-COM', 'is_active' => true]);
        $large = Size::query()->create(['name' => 'L', 'code' => 'L-COM', 'is_active' => true]);
        $this->source = ProductVariant::query()->create([
            'product_id' => $this->product->getKey(), 'color_id' => $black->getKey(), 'size_id' => $medium->getKey(),
            'name' => 'مشکی / M', 'sku' => 'COM-SHIRT-BLK-M', 'regular_price_toman' => 100_000,
            'sale_price_toman' => 80_000, 'stock_quantity' => 5, 'low_stock_threshold' => 1, 'is_default' => true, 'is_active' => true,
        ]);
        $this->destination = ProductVariant::query()->create([
            'product_id' => $this->product->getKey(), 'color_id' => $red->getKey(), 'size_id' => $large->getKey(),
            'name' => 'قرمز / L', 'sku' => 'COM-SHIRT-RED-L', 'regular_price_toman' => 100_000,
            'stock_quantity' => 2, 'low_stock_threshold' => 1, 'is_active' => true,
        ]);
        DB::table('products')->where('id', $this->product->getKey())->update([
            'publication_status' => PublicationStatus::Published->value, 'published_at' => now(),
        ]);
        $this->product->refresh();
    }

    public function test_v1_cart_quote_and_commit_are_server_authoritative_idempotent_and_ledger_backed(): void
    {
        $this->postCart('/api/v1/cart/validate', 1, expected: 70_000)
            ->assertConflict()->assertJsonPath('code', 'commerce_price_changed');

        $quote = $this->postCart('/api/v1/checkout/quote', 2, expected: 80_000)
            ->assertCreated()
            ->assertJsonPath('data.totals.subtotal.amount', 160_000)
            ->assertJsonPath('data.totals.grandTotal.amount', 200_000)
            ->assertJsonPath('data.currency', 'TOMAN');
        $this->assertDatabaseCount('inventory_reservations', 0);

        $quoteId = $quote->json('data.quoteId');
        $commit = $this->actingAs($this->customer, 'customer')->postJson('/api/v1/checkout/commit', ['quoteId' => $quoteId], [
            'Idempotency-Key' => 'commerce-commit-000001',
        ])->assertCreated()
            ->assertJsonPath('data.order.items.0.color', 'مشکی')
            ->assertJsonPath('data.order.items.0.size', 'M')
            ->assertJsonPath('data.order.items.0.unitPrice.currency', 'TOMAN')
            ->assertJsonPath('meta.replayed', false);
        $orderId = $commit->json('data.order.id');

        $this->actingAs($this->customer, 'customer')->postJson('/api/v1/checkout/commit', ['quoteId' => $quoteId], [
            'Idempotency-Key' => 'commerce-commit-000001',
        ])->assertOk()->assertJsonPath('data.order.id', $orderId)->assertJsonPath('meta.replayed', true);

        $this->assertSame(5, $this->source->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_reservations', [
            'variant_id' => $this->source->getKey(), 'quantity' => 2, 'status' => InventoryReservationStatus::Active->value,
        ]);
        $this->assertDatabaseHas('inventory_ledger_entries', ['variant_id' => $this->source->getKey(), 'event_type' => InventoryLedgerEventType::OpeningBalance->value]);
        $this->assertDatabaseHas('inventory_ledger_entries', ['variant_id' => $this->source->getKey(), 'event_type' => InventoryLedgerEventType::ReservationCreated->value, 'reserved_delta' => 2]);
    }

    public function test_checkout_commit_rolls_back_when_truth_changes_after_quote(): void
    {
        $quote = $this->postCart('/api/v1/checkout/quote', 2, expected: 80_000)->assertCreated();
        app(InventoryLedgerService::class)->adjust($this->source, -4, 'test adjustment', 'test-adjust-before-commit');

        $this->actingAs($this->customer, 'customer')->postJson('/api/v1/checkout/commit', ['quoteId' => $quote->json('data.quoteId')], [
            'Idempotency-Key' => 'commerce-commit-rollback',
        ])->assertConflict()->assertJsonPath('code', 'commerce_out_of_stock');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_reservations', 0);
    }

    public function test_direct_stock_mutation_is_blocked_and_adjustment_respects_active_reservations(): void
    {
        try {
            $this->source->update(['stock_quantity' => 4]);
            $this->fail('Direct stock mutation should be blocked.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('InventoryLedgerService', $exception->getMessage());
        }
        $order = $this->commitOrder(2, 'commerce-ledger-order');
        $ledger = app(InventoryLedgerService::class);
        try {
            $ledger->adjust($this->source, -4, 'would undercut reservation', 'adjust-under-reservation');
            $this->fail('Expected stock constraint failure.');
        } catch (CommerceException $exception) {
            $this->assertSame('commerce_out_of_stock', $exception->commerceCode->value);
        }
        $ledger->adjust($this->source, 2, 'verified admin correction', 'adjust-safe', 'admin', 1);
        $this->assertSame(7, $this->source->fresh()->stock_quantity);
        $this->assertDatabaseHas('commerce_audit_events', ['order_id' => null, 'event_key' => 'inventory.adjusted']);
        $this->assertNotNull($order);
    }

    public function test_expiration_is_deterministic_and_traced(): void
    {
        $order = $this->commitOrder(1, 'commerce-expire-order');
        $order->update(['reservation_expires_at' => now()->subMinute()]);
        InventoryReservation::query()->where('order_id', $order->getKey())->update(['expires_at' => now()->subMinute()]);
        Artisan::call('inventory:release-expired');
        $this->assertSame(OrderStatus::Expired, $order->fresh()->status);
        $this->assertDatabaseHas('inventory_reservations', ['order_id' => $order->getKey(), 'status' => InventoryReservationStatus::Expired->value]);
        $this->assertDatabaseHas('inventory_ledger_entries', ['order_id' => $order->getKey(), 'event_type' => InventoryLedgerEventType::ReservationExpired->value]);
    }

    public function test_paid_cancellation_restocks_once_and_creates_fail_closed_refund_state(): void
    {
        $order = $this->deliveredOrder(1, 'commerce-cancel-paid');
        $lifecycle = app(OrderLifecycleService::class);
        // Delivered cannot be cancelled, so use a paid order before fulfillment for cancellation.
        $order->forceFill(['status' => OrderStatus::Paid, 'delivered_at' => null])->save();
        $cancelled = $lifecycle->transitionByAdmin($order, OrderStatus::Cancelled, 1, 'لغو تست');
        $this->assertSame(5, $this->source->fresh()->stock_quantity);
        $refund = RefundRequest::query()->where('order_id', $cancelled->getKey())->firstOrFail();
        $this->assertSame(RefundStatus::Requested, $refund->status);
        $this->assertSame(PaymentStatus::Paid, $cancelled->payment_status);
        try {
            app(RefundService::class)->markPending($refund, 1);
            $this->fail('Refund must fail closed while no real refund provider is enabled.');
        } catch (CommerceException $exception) {
            $this->assertSame('commerce_refund_unavailable', $exception->commerceCode->value);
        }
    }

    public function test_return_lifecycle_can_restock_received_goods_and_idor_is_blocked(): void
    {
        $order = $this->deliveredOrder(1, 'commerce-return-order');
        $line = $order->items->first();
        $created = $this->actingAs($this->customer, 'customer')->postJson("/api/v1/orders/{$order->public_id}/returns", [
            'reason' => 'سایز مناسب نیست', 'items' => [['orderItemId' => $line->public_id, 'quantity' => 1]],
        ], ['Idempotency-Key' => 'return-request-000001'])->assertCreated();
        $returnId = $created->json('data.return.id');
        $return = ReturnRequest::query()->where('public_id', $returnId)->firstOrFail();
        $service = app(ReturnService::class);
        $return = $service->approve($return, 1);
        $return = $service->receive($return, 1);
        $return = $service->resolve($return, ReturnResolution::NoFinancialAction, true, 1);
        $this->assertSame(ReturnStatus::Resolved, $return->status);
        $this->assertSame(5, $this->source->fresh()->stock_quantity);

        $other = Customer::query()->create(['mobile' => '09122222222', 'mobile_verified_at' => now(), 'is_active' => true]);
        $this->actingAs($other, 'customer')->getJson("/api/v1/returns/{$returnId}")->assertNotFound();
    }

    public function test_exchange_reserves_destination_consumes_it_and_returns_source_stock(): void
    {
        $order = $this->deliveredOrder(1, 'commerce-exchange-order');
        $line = $order->items->first();
        $created = $this->actingAs($this->customer, 'customer')->postJson("/api/v1/orders/{$order->public_id}/exchanges", [
            'orderItemId' => $line->public_id, 'destinationVariantId' => $this->destination->public_id,
            'quantity' => 1, 'reason' => 'نیاز به سایز دیگر',
        ], ['Idempotency-Key' => 'exchange-request-0001'])->assertCreated();
        $exchange = ExchangeRequest::query()->where('public_id', $created->json('data.exchange.id'))->firstOrFail();
        $service = app(ExchangeService::class);
        $exchange = $service->approve($exchange, 1);
        $this->assertSame(ExchangeStatus::Approved, $exchange->status);
        $this->assertDatabaseHas('inventory_reservations', ['id' => $exchange->destination_reservation_id, 'purpose' => 'exchange', 'status' => 'active']);
        $exchange = $service->complete($exchange, 1);
        $this->assertSame(ExchangeStatus::Completed, $exchange->status);
        $this->assertSame(5, $this->source->fresh()->stock_quantity);
        $this->assertSame(1, $this->destination->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_ledger_entries', ['variant_id' => $this->destination->getKey(), 'event_type' => InventoryLedgerEventType::ExchangeOut->value]);
        $this->assertDatabaseHas('inventory_ledger_entries', ['variant_id' => $this->source->getKey(), 'event_type' => InventoryLedgerEventType::ExchangeIn->value]);
    }

    public function test_exchange_destination_must_be_available(): void
    {
        app(InventoryLedgerService::class)->adjust($this->destination, -2, 'empty destination', 'empty-destination');
        $order = $this->deliveredOrder(1, 'commerce-exchange-empty');
        $line = $order->items->first();
        $this->actingAs($this->customer, 'customer')->postJson("/api/v1/orders/{$order->public_id}/exchanges", [
            'orderItemId' => $line->public_id, 'destinationVariantId' => $this->destination->public_id, 'quantity' => 1,
        ], ['Idempotency-Key' => 'exchange-request-empty'])->assertConflict()->assertJsonPath('code', 'commerce_out_of_stock');
    }

    public function test_payment_replay_is_recorded_once_and_wrong_server_amount_fails_closed(): void
    {
        config([
            'lbb.payment.enabled' => true,
            'lbb.payment.provider' => 'testing',
            'lbb.payment.amount_multiplier' => 10,
            'lbb.payment.attempt_ttl_minutes' => 20,
        ]);

        $order = $this->commitOrder(1, 'commerce-payment-order');
        $init = $this->actingAs($this->customer, 'customer')->postJson("/api/v1/orders/{$order->public_id}/payments", [], [
            'Idempotency-Key' => 'commerce-payment-attempt-1',
        ])->assertCreated();
        $authority = $init->json('data.payment.authority');
        $this->actingAs($this->customer, 'customer')->postJson('/api/v1/payments/verify', [
            'authority' => $authority, 'status' => 'OK',
        ])->assertOk()->assertJsonPath('data.verified', true)->assertJsonPath('meta.replayed', false);
        $this->assertSame(4, $this->source->fresh()->stock_quantity);
        $this->assertDatabaseCount('payment_callback_events', 1);

        $this->actingAs($this->customer, 'customer')->postJson('/api/v1/payments/verify', [
            'authority' => $authority, 'status' => 'OK',
        ])->assertOk()->assertJsonPath('meta.replayed', true);
        $this->assertSame(4, $this->source->fresh()->stock_quantity);
        $this->assertDatabaseCount('payment_callback_events', 1);

        $order2 = $this->commitOrder(1, 'commerce-payment-wrong-order');
        $init2 = $this->actingAs($this->customer, 'customer')->postJson("/api/v1/orders/{$order2->public_id}/payments", [], [
            'Idempotency-Key' => 'commerce-payment-attempt-2',
        ])->assertCreated();
        DB::table('payment_attempts')->where('public_id', $init2->json('data.payment.id'))->update(['amount_toman' => $order2->grand_total_toman + 1]);
        $this->actingAs($this->customer, 'customer')->postJson('/api/v1/payments/verify', [
            'authority' => $init2->json('data.payment.authority'), 'status' => 'OK',
        ])->assertStatus(502)->assertJsonPath('code', 'commerce_payment_unavailable');
        $this->assertSame(OrderStatus::AwaitingPayment, $order2->fresh()->status);
    }

    public function test_sensitive_commerce_rate_limit_is_enforced(): void
    {
        $payload = [
            'reason' => 'rate-limit-test',
            'items' => [['orderItemId' => '00000000000000000000000000', 'quantity' => 1]],
        ];
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->customer, 'customer')->postJson('/api/v1/orders/00000000000000000000000000/returns', $payload, [
                'Idempotency-Key' => 'rate-limit-return-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            ])->assertNotFound();
        }
        $this->actingAs($this->customer, 'customer')->postJson('/api/v1/orders/00000000000000000000000000/returns', $payload, [
            'Idempotency-Key' => 'rate-limit-return-0010',
        ])->assertTooManyRequests();
    }

    private function postCart(string $endpoint, int $quantity, ?int $expected = null)
    {
        $item = ['variantId' => $this->source->public_id, 'quantity' => $quantity];
        if ($expected !== null) {
            $item['expectedUnitPriceToman'] = $expected;
        }

        return $this->actingAs($this->customer, 'customer')->postJson($endpoint, [
            'customer' => ['fullName' => 'مشتری Commerce', 'mobile' => '09121111111', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'آدرس تست', 'postalCode' => '1234567890'],
            'deliveryMethod' => 'standard', 'items' => [$item],
        ]);
    }

    private function commitOrder(int $quantity, string $key): Order
    {
        $quote = $this->postCart('/api/v1/checkout/quote', $quantity, 80_000)->assertCreated();
        $response = $this->actingAs($this->customer, 'customer')->postJson('/api/v1/checkout/commit', ['quoteId' => $quote->json('data.quoteId')], [
            'Idempotency-Key' => $key,
        ])->assertCreated();

        return Order::query()->where('public_id', $response->json('data.order.id'))->with(['items', 'reservations', 'shipment'])->firstOrFail();
    }

    private function deliveredOrder(int $quantity, string $key): Order
    {
        $order = $this->commitOrder($quantity, $key);
        app(OrderLifecycleService::class)->consumeReservations($order);
        $order->forceFill([
            'status' => OrderStatus::Delivered, 'payment_status' => PaymentStatus::Paid, 'paid_at' => now()->subHour(), 'delivered_at' => now(),
        ])->save();

        return $order->fresh(['items', 'reservations', 'shipment']);
    }
}
