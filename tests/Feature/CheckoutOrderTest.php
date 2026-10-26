<?php

namespace Tests\Feature;

use App\Enums\InventoryReservationStatus;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Customer;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CheckoutOrderTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'lbb.checkout.enabled' => true,
            'lbb.checkout.reservation_minutes' => 20,
            'lbb.checkout.max_quantity_per_line' => 20,
            'lbb.checkout.max_total_units' => 50,
            'lbb.checkout.packaging_fee_toman' => 10_000,
            'lbb.checkout.delivery_methods.standard' => ['enabled' => true, 'fee_toman' => 30_000],
            'lbb.checkout.delivery_methods.pickup' => ['enabled' => true, 'fee_toman' => 0],
            'session.driver' => 'array',
        ]);

        $this->customer = Customer::query()->create([
            'mobile' => '09123456780',
            'full_name' => 'سجاد خواص',
            'mobile_verified_at' => now(),
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'دسته آزمایشی',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'محصول آزمایشی',
            'slug' => 'test-product',
            'product_code' => 'LBB-TEST-001',
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'name' => 'انتخاب استاندارد',
            'sku' => 'LBB-TEST-001-A',
            'regular_price_toman' => 100_000,
            'sale_price_toman' => 80_000,
            'stock_quantity' => 5,
            'low_stock_threshold' => 2,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_checkout_uses_server_prices_snapshots_and_reserves_without_decrementing_physical_stock(): void
    {
        $response = $this->checkout('checkout-key-000001', 2);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.totals.subtotalToman', 160_000)
            ->assertJsonPath('data.order.totals.deliveryFeeToman', 30_000)
            ->assertJsonPath('data.order.totals.packagingFeeToman', 10_000)
            ->assertJsonPath('data.order.totals.grandTotalToman', 200_000)
            ->assertJsonPath('data.order.items.0.unitPriceToman', 80_000)
            ->assertJsonPath('data.order.items.0.productName', 'محصول آزمایشی')
            ->assertJsonPath('data.payment.available', false)
            ->assertJsonPath('meta.replayed', false);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->getKey(),
            'status' => OrderStatus::AwaitingPayment->value,
            'subtotal_toman' => 160_000,
            'grand_total_toman' => 200_000,
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_name' => 'محصول آزمایشی',
            'variant_name' => 'انتخاب استاندارد',
            'unit_price_toman' => 80_000,
            'quantity' => 2,
            'line_total_toman' => 160_000,
        ]);
        $this->assertDatabaseHas('inventory_reservations', [
            'variant_id' => $this->variant->getKey(),
            'quantity' => 2,
            'status' => InventoryReservationStatus::Active->value,
        ]);
        $this->assertSame(5, $this->variant->fresh()->stock_quantity);

        $this->getJson('/api/catalog/products/test-product')
            ->assertOk()
            ->assertJsonPath('data.variants.0.stock', 3);
    }

    public function test_same_idempotency_key_replays_one_order_and_rejects_a_different_payload(): void
    {
        $first = $this->checkout('checkout-key-000002', 1)
            ->assertCreated();
        $orderId = $first->json('data.order.id');

        $this->checkout('checkout-key-000002', 1)
            ->assertOk()
            ->assertJsonPath('data.order.id', $orderId)
            ->assertJsonPath('meta.replayed', true);

        $this->checkout('checkout-key-000002', 2)
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('inventory_reservations', 1);
    }

    public function test_active_reservations_prevent_overselling(): void
    {
        $this->checkout('checkout-key-000003', 4)->assertCreated();

        $this->checkout('checkout-key-000004', 2)
            ->assertUnprocessable()
            ->assertJsonPath('errors.items.0.variantId', $this->variant->public_id)
            ->assertJsonPath('errors.items.0.available', 1);

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(5, $this->variant->fresh()->stock_quantity);
    }


    public function test_customer_can_only_view_own_orders_and_cancel_unpaid_order(): void
    {
        $created = $this->checkout('checkout-key-000007', 2)->assertCreated();
        $orderId = $created->json('data.order.id');

        $this->actingAs($this->customer, 'customer')
            ->getJson('/api/account/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $orderId);

        $other = Customer::query()->create([
            'mobile' => '09123456781',
            'mobile_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->actingAs($other, 'customer')
            ->getJson("/api/account/orders/{$orderId}")
            ->assertNotFound();

        $this->actingAs($this->customer, 'customer')
            ->postJson("/api/account/orders/{$orderId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.order.status', OrderStatus::Cancelled->value);

        $this->assertDatabaseHas('inventory_reservations', [
            'status' => InventoryReservationStatus::Released->value,
            'release_reason' => 'customer_cancelled',
        ]);
        $this->assertSame(5, $this->variant->fresh()->stock_quantity);
    }

    public function test_expired_orders_release_reservations_and_payment_consumption_decrements_stock_once(): void
    {
        $created = $this->checkout('checkout-key-000008', 2)->assertCreated();
        $order = Order::query()->where('public_id', $created->json('data.order.id'))->firstOrFail();

        $order->update(['reservation_expires_at' => now()->subMinute()]);
        InventoryReservation::query()->where('order_id', $order->getKey())->update([
            'expires_at' => now()->subMinute(),
        ]);

        Artisan::call('inventory:release-expired');

        $this->assertSame(OrderStatus::Expired, $order->fresh()->status);
        $this->assertDatabaseHas('inventory_reservations', [
            'order_id' => $order->getKey(),
            'status' => InventoryReservationStatus::Expired->value,
            'release_reason' => 'payment_timeout',
        ]);

        $paidCandidate = $this->checkout('checkout-key-000009', 2)->assertCreated();
        $paidOrder = Order::query()->where('public_id', $paidCandidate->json('data.order.id'))->firstOrFail();
        app(OrderLifecycleService::class)->consumeReservations($paidOrder);

        $this->assertSame(3, $this->variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_reservations', [
            'order_id' => $paidOrder->getKey(),
            'status' => InventoryReservationStatus::Consumed->value,
        ]);
    }

    public function test_checkout_is_disabled_by_default_and_requires_a_valid_idempotency_key(): void
    {
        config(['lbb.checkout.enabled' => false]);

        $this->checkout('checkout-key-000010', 1)
            ->assertServiceUnavailable();

        config(['lbb.checkout.enabled' => true]);

        $this->actingAs($this->customer, 'customer')
            ->postJson('/api/checkout', $this->payload(1), [
                'Idempotency-Key' => 'short',
            ])
            ->assertUnprocessable();
    }

    private function checkout(string $key, int $quantity, string $deliveryMethod = 'standard')
    {
        return $this->actingAs($this->customer, 'customer')
            ->postJson('/api/checkout', $this->payload($quantity, $deliveryMethod), [
                'Idempotency-Key' => $key,
                'Origin' => 'http://localhost:5173',
                'Referer' => 'http://localhost:5173/',
            ]);
    }

    private function payload(int $quantity, string $deliveryMethod = 'standard'): array
    {
        return [
            'customer' => [
                'fullName' => 'سجاد خواص',
                'mobile' => '09123456780',
                'province' => 'تهران',
                'city' => 'تهران',
                'address' => 'خیابان نمونه، پلاک ۱',
                'postalCode' => '1234567890',
                'notes' => 'زنگ واحد را بزنید.',
            ],
            'deliveryMethod' => $deliveryMethod,
            'items' => [[
                'variantId' => $this->variant->public_id,
                'quantity' => $quantity,
            ]],
        ];
    }
}
