<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\InventoryReservationStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Customer;
use App\Models\InventoryReservation;
use App\Models\NotificationOutbox;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Product $product;

    private ProductVariant $variant;

    private int $adminId;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'lbb.notifications.sms_provider' => 'disabled',
            'lbb.notifications.max_attempts' => 5,
            'lbb.notifications.retry_seconds' => 60,
        ]);

        $this->adminId = User::query()->create([
            'name' => 'مدیر عملیات تست',
            'email' => 'fulfillment-admin@example.test',
            'password' => 'test-password',
        ])->getKey();

        $this->customer = Customer::query()->create([
            'mobile' => '09120000000',
            'full_name' => 'مشتری عملیات',
            'mobile_verified_at' => now(),
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'کوکی',
            'slug' => 'fulfillment-cookies',
            'is_active' => true,
        ]);

        $this->product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'کوکی عملیات',
            'slug' => 'fulfillment-cookie',
            'product_code' => 'FUL-COOKIE',
            'preparation_time_days' => 1,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::query()->create([
            'product_id' => $this->product->getKey(),
            'name' => 'بسته تست',
            'sku' => 'FUL-COOKIE-1',
            'regular_price_toman' => 100_000,
            'stock_quantity' => 3,
            'low_stock_threshold' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_admin_fulfillment_transitions_are_controlled_and_queue_public_notifications(): void
    {
        $order = $this->createPaidOrder(DeliveryMethod::Standard, 'LBB-FUL-0001');
        $lifecycle = app(OrderLifecycleService::class);

        $this->assertValidationFailure(
            fn () => $lifecycle->transitionByAdmin($order, OrderStatus::Preparing, $this->adminId),
        );

        $order = $lifecycle->transitionByAdmin($order, OrderStatus::Confirmed, $this->adminId, 'تأیید سفارش');
        $order = $lifecycle->transitionByAdmin($order, OrderStatus::Preparing, $this->adminId);
        $order = $lifecycle->transitionByAdmin($order, OrderStatus::Ready, $this->adminId);

        $this->assertValidationFailure(
            fn () => $lifecycle->transitionByAdmin($order, OrderStatus::Delivered, $this->adminId),
        );
        $this->assertValidationFailure(
            fn () => $lifecycle->transitionByAdmin($order, OrderStatus::Dispatched, $this->adminId),
        );

        $order = $lifecycle->transitionByAdmin(
            $order,
            OrderStatus::Dispatched,
            $this->adminId,
            'تحویل به پیک',
            'TRACK-123',
        );
        $order = $lifecycle->transitionByAdmin($order, OrderStatus::Delivered, $this->adminId);
        $lifecycle->addInternalNote($order, $this->adminId, 'این یادداشت فقط برای مدیر است.');

        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertSame('TRACK-123', $order->tracking_code);
        $this->assertNotNull($order->confirmed_at);
        $this->assertNotNull($order->preparing_at);
        $this->assertNotNull($order->ready_at);
        $this->assertNotNull($order->dispatched_at);
        $this->assertNotNull($order->delivered_at);
        $this->assertDatabaseHas('order_internal_notes', [
            'order_id' => $order->getKey(),
            'user_id' => $this->adminId,
            'note' => 'این یادداشت فقط برای مدیر است.',
        ]);

        $this->assertSame([
            'order.preparing',
            'order.ready',
            'order.dispatched',
            'order.delivered',
        ], NotificationOutbox::query()->where('order_id', $order->getKey())->orderBy('id')->pluck('template_key')->all());
    }

    public function test_admin_cancellation_after_payment_restocks_consumed_inventory_exactly_once(): void
    {
        $order = $this->createPaidOrder(DeliveryMethod::Pickup, 'LBB-FUL-0002');
        $lifecycle = app(OrderLifecycleService::class);

        $cancelled = $lifecycle->transitionByAdmin(
            $order,
            OrderStatus::Cancelled,
            $this->adminId,
            'لغو مدیریتی و نیازمند پیگیری بازپرداخت',
        );

        $this->assertSame(OrderStatus::Cancelled, $cancelled->status);
        $this->assertSame(PaymentStatus::Paid, $cancelled->payment_status);
        $this->assertSame(5, $this->variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_reservations', [
            'order_id' => $order->getKey(),
            'status' => InventoryReservationStatus::Restocked->value,
            'release_reason' => 'admin_cancelled_after_payment',
        ]);

        $this->assertValidationFailure(
            fn () => $lifecycle->transitionByAdmin($cancelled, OrderStatus::Cancelled, $this->adminId),
        );

        $this->assertSame(5, $this->variant->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_reservations', 1);
    }

    public function test_pickup_order_skips_dispatch_and_can_be_delivered_from_ready(): void
    {
        $order = $this->createPaidOrder(DeliveryMethod::Pickup, 'LBB-FUL-0003');
        $lifecycle = app(OrderLifecycleService::class);
        $order = $lifecycle->transitionByAdmin($order, OrderStatus::Confirmed, $this->adminId);
        $order = $lifecycle->transitionByAdmin($order, OrderStatus::Preparing, $this->adminId);
        $order = $lifecycle->transitionByAdmin($order, OrderStatus::Ready, $this->adminId);

        $this->assertValidationFailure(
            fn () => $lifecycle->transitionByAdmin(
                $order,
                OrderStatus::Dispatched,
                $this->adminId,
                trackingCode: 'INVALID-PICKUP',
            ),
        );

        $delivered = $lifecycle->transitionByAdmin($order, OrderStatus::Delivered, $this->adminId);

        $this->assertSame(OrderStatus::Delivered, $delivered->status);
        $this->assertNull($delivered->dispatched_at);
        $this->assertNotNull($delivered->delivered_at);
    }

    private function createPaidOrder(DeliveryMethod $deliveryMethod, string $number): Order
    {
        $order = Order::query()->create([
            'customer_id' => $this->customer->getKey(),
            'order_number' => $number,
            'idempotency_key' => strtolower($number).'-idempotency-key',
            'request_hash' => hash('sha256', $number),
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'delivery_method' => $deliveryMethod,
            'subtotal_toman' => 200_000,
            'delivery_fee_toman' => 0,
            'packaging_fee_toman' => 0,
            'discount_total_toman' => 0,
            'grand_total_toman' => 200_000,
            'item_count' => 2,
            'preparation_time_days' => 1,
            'preparation_max_days' => 2,
            'customer_name' => 'مشتری عملیات',
            'customer_mobile' => '09120000000',
            'province' => $deliveryMethod->requiresAddress() ? 'تهران' : null,
            'city' => $deliveryMethod->requiresAddress() ? 'تهران' : null,
            'address' => $deliveryMethod->requiresAddress() ? 'آدرس تست' : null,
            'placed_at' => now()->subHour(),
            'paid_at' => now()->subMinutes(50),
        ]);

        $order->items()->create([
            'product_id' => $this->product->getKey(),
            'variant_id' => $this->variant->getKey(),
            'product_public_id' => $this->product->public_id,
            'variant_public_id' => $this->variant->public_id,
            'product_name' => $this->product->name,
            'variant_name' => $this->variant->name,
            'product_code' => $this->product->product_code,
            'sku' => $this->variant->sku,
            'unit_price_toman' => 100_000,
            'quantity' => 2,
            'line_total_toman' => 200_000,
        ]);

        InventoryReservation::query()->create([
            'order_id' => $order->getKey(),
            'variant_id' => $this->variant->getKey(),
            'quantity' => 2,
            'status' => InventoryReservationStatus::Consumed,
            'expires_at' => now()->addHour(),
            'consumed_at' => now()->subMinutes(50),
        ]);

        return $order->fresh(['items', 'reservations']);
    }

    private function assertValidationFailure(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
    }
}
