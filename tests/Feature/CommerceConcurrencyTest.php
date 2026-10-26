<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\CommerceException;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Commerce\InventoryLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_unit_cannot_be_reserved_twice(): void
    {
        [$order, $variant] = $this->fixture();
        $inventory = app(InventoryLedgerService::class);

        $inventory->reserveForOrder($order, $variant, 1, now()->addMinutes(10), 'test', 'sequential-guard-1');

        try {
            $inventory->reserveForOrder($order, $variant, 1, now()->addMinutes(10), 'test', 'sequential-guard-2');
            $this->fail('Second reservation should not succeed.');
        } catch (CommerceException $exception) {
            $this->assertSame('commerce_out_of_stock', $exception->commerceCode->value);
        }

        $this->assertDatabaseCount('inventory_reservations', 1);
        $this->assertDatabaseCount('inventory_ledger_entries', 2);
    }

    private function fixture(): array
    {
        $customer = Customer::query()->create(['mobile' => '09123334444', 'mobile_verified_at' => now(), 'is_active' => true]);
        $category = Category::query()->create(['name' => 'Race', 'slug' => 'race', 'is_active' => true]);
        $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Race Product', 'slug' => 'race-product', 'product_code' => 'RACE', 'is_active' => true]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id, 'name' => 'Race Variant', 'sku' => 'RACE-1', 'regular_price_toman' => 1000,
            'stock_quantity' => 1, 'low_stock_threshold' => 0, 'is_default' => true, 'is_active' => true,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id, 'order_number' => 'LBB-RACE-1', 'idempotency_key' => 'race-order-key-0001',
            'request_hash' => hash('sha256', 'race'), 'status' => OrderStatus::AwaitingPayment, 'payment_status' => PaymentStatus::Unpaid,
            'delivery_method' => 'pickup', 'subtotal_toman' => 1000, 'delivery_fee_toman' => 0, 'packaging_fee_toman' => 0,
            'discount_total_toman' => 0, 'grand_total_toman' => 1000, 'currency' => 'TOMAN', 'item_count' => 1,
            'preparation_time_days' => 0, 'preparation_max_days' => 0, 'customer_name' => 'Race', 'customer_mobile' => '09123334444',
            'reservation_expires_at' => now()->addMinutes(20), 'placed_at' => now(),
        ]);

        return [$order, $variant];
    }
}
