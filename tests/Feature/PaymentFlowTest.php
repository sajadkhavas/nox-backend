<?php

namespace Tests\Feature;

use App\Enums\InventoryReservationStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Customer;
use App\Models\PaymentAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'session.driver' => 'array',
            'lbb.checkout.enabled' => true,
            'lbb.checkout.reservation_minutes' => 20,
            'lbb.checkout.max_quantity_per_line' => 20,
            'lbb.checkout.max_total_units' => 50,
            'lbb.checkout.packaging_fee_toman' => 10_000,
            'lbb.checkout.delivery_methods.standard' => ['enabled' => true, 'fee_toman' => 30_000],
            'lbb.checkout.delivery_methods.chilled' => ['enabled' => true, 'fee_toman' => 90_000],
            'lbb.checkout.delivery_methods.pickup' => ['enabled' => true, 'fee_toman' => 0],
            'lbb.payment.enabled' => true,
            'lbb.payment.provider' => 'testing',
            'lbb.payment.callback_url' => 'http://localhost:5173/payment/result',
            'lbb.payment.amount_multiplier' => 10,
            'lbb.payment.attempt_ttl_minutes' => 20,
            'lbb.payment.timeout_seconds' => 5,
        ]);

        $this->customer = Customer::query()->create([
            'mobile' => '09123456780',
            'full_name' => 'سجاد خواص',
            'mobile_verified_at' => now(),
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'شیرینی',
            'slug' => 'pastry',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'کوکی شکلاتی',
            'slug' => 'chocolate-cookie',
            'product_code' => 'COOKIE-001',
            'preparation_time_days' => 2,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'name' => 'بسته ۶ عددی',
            'sku' => 'COOKIE-001-6',
            'regular_price_toman' => 100_000,
            'sale_price_toman' => 80_000,
            'stock_quantity' => 5,
            'low_stock_threshold' => 2,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_checkout_reports_provider_ready_payment_without_initiating_it(): void
    {
        $response = $this->checkout('payment-checkout-0001', 1);

        $response
            ->assertCreated()
            ->assertJsonPath('data.payment.available', true)
            ->assertJsonPath('data.payment.state', 'ready');

        $this->assertDatabaseCount('payment_attempts', 0);
    }

    public function test_payment_initiation_is_idempotent_and_reuses_the_active_attempt(): void
    {
        $orderId = $this->checkout('payment-checkout-0002', 1)
            ->assertCreated()
            ->json('data.order.id');

        $first = $this->initiate($orderId, 'payment-attempt-key-0001')
            ->assertCreated()
            ->assertJsonPath('data.payment.status', PaymentAttemptStatus::Pending->value)
            ->assertJsonPath('data.payment.provider', 'testing')
            ->assertJsonPath('data.order.paymentStatus', PaymentStatus::Pending->value)
            ->assertJsonPath('meta.replayed', false);

        $attemptId = $first->json('data.payment.id');

        $this->initiate($orderId, 'payment-attempt-key-0001')
            ->assertOk()
            ->assertJsonPath('data.payment.id', $attemptId)
            ->assertJsonPath('meta.replayed', true);

        $this->initiate($orderId, 'payment-attempt-key-0002')
            ->assertOk()
            ->assertJsonPath('data.payment.id', $attemptId)
            ->assertJsonPath('meta.replayed', true);

        $this->assertDatabaseCount('payment_attempts', 1);
    }

    public function test_verified_payment_marks_order_paid_and_consumes_stock_exactly_once(): void
    {
        $orderId = $this->checkout('payment-checkout-0003', 2)
            ->assertCreated()
            ->json('data.order.id');
        $authority = $this->initiate($orderId, 'payment-attempt-key-0003')
            ->assertCreated()
            ->json('data.payment.authority');

        $this->verify($authority, 'OK')
            ->assertOk()
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.order.status', OrderStatus::Paid->value)
            ->assertJsonPath('data.order.paymentStatus', PaymentStatus::Paid->value)
            ->assertJsonPath('data.payment.status', PaymentAttemptStatus::Verified->value)
            ->assertJsonPath('meta.replayed', false);

        $this->assertSame(3, $this->variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_reservations', [
            'status' => InventoryReservationStatus::Consumed->value,
            'quantity' => 2,
        ]);

        $this->verify($authority, 'OK')
            ->assertOk()
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('meta.replayed', true);

        $this->assertSame(3, $this->variant->fresh()->stock_quantity);
        $this->assertDatabaseCount('payment_attempts', 1);
    }

    public function test_cancelled_attempt_keeps_reservation_and_allows_a_new_retry_attempt(): void
    {
        $orderId = $this->checkout('payment-checkout-0004', 2)
            ->assertCreated()
            ->json('data.order.id');
        $authority = $this->initiate($orderId, 'payment-attempt-key-0004')
            ->assertCreated()
            ->json('data.payment.authority');

        $this->verify($authority, 'NOK')
            ->assertOk()
            ->assertJsonPath('data.verified', false)
            ->assertJsonPath('data.payment.status', PaymentAttemptStatus::Cancelled->value)
            ->assertJsonPath('data.order.paymentStatus', PaymentStatus::Failed->value);

        $this->assertSame(5, $this->variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_reservations', [
            'status' => InventoryReservationStatus::Active->value,
            'quantity' => 2,
        ]);

        $this->initiate($orderId, 'payment-attempt-key-retry-0004')
            ->assertCreated()
            ->assertJsonPath('data.payment.attemptNumber', 2)
            ->assertJsonPath('data.payment.status', PaymentAttemptStatus::Pending->value);

        $this->assertDatabaseCount('payment_attempts', 2);
    }

    public function test_customer_cannot_initiate_or_verify_another_customers_payment(): void
    {
        $orderId = $this->checkout('payment-checkout-0005', 1)
            ->assertCreated()
            ->json('data.order.id');
        $authority = $this->initiate($orderId, 'payment-attempt-key-0005')
            ->assertCreated()
            ->json('data.payment.authority');

        $other = Customer::query()->create([
            'mobile' => '09123456781',
            'mobile_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->actingAs($other, 'customer')
            ->postJson("/api/orders/{$orderId}/payments", [], [
                'Idempotency-Key' => 'other-customer-key-0001',
            ])
            ->assertNotFound();

        $this->actingAs($other, 'customer')
            ->postJson('/api/payments/verify', [
                'authority' => $authority,
                'status' => 'OK',
            ])
            ->assertNotFound();
    }

    public function test_payment_is_disabled_by_default_even_when_checkout_exists(): void
    {
        $orderId = $this->checkout('payment-checkout-0006', 1)
            ->assertCreated()
            ->json('data.order.id');

        config([
            'lbb.payment.enabled' => false,
            'lbb.payment.provider' => 'disabled',
        ]);

        $this->initiate($orderId, 'payment-attempt-key-0006')
            ->assertServiceUnavailable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('payment_attempts', 0);
    }

    public function test_zarinpal_adapter_uses_server_amount_and_verifies_the_recorded_authority(): void
    {
        config([
            'lbb.payment.provider' => 'zarinpal',
            'lbb.payment.zarinpal.merchant_id' => '00000000-0000-0000-0000-000000000000',
            'lbb.payment.zarinpal.request_url' => 'https://gateway.test/request',
            'lbb.payment.zarinpal.verify_url' => 'https://gateway.test/verify',
            'lbb.payment.zarinpal.start_pay_url' => 'https://gateway.test/start',
        ]);

        Http::fake([
            'https://gateway.test/request' => Http::response([
                'data' => [
                    'code' => 100,
                    'authority' => 'A000000000000000000000000000000001',
                ],
                'errors' => [],
            ]),
            'https://gateway.test/verify' => Http::response([
                'data' => [
                    'code' => 100,
                    'ref_id' => 987654321,
                    'card_pan' => '6037-****-****-0000',
                    'card_hash' => 'sensitive-card-hash',
                ],
                'errors' => [],
            ]),
        ]);

        $orderId = $this->checkout('payment-checkout-0007', 1)
            ->assertCreated()
            ->json('data.order.id');

        $this->initiate($orderId, 'payment-attempt-key-0007')
            ->assertCreated()
            ->assertJsonPath('data.payment.authority', 'A000000000000000000000000000000001')
            ->assertJsonPath('data.payment.redirectUrl', 'https://gateway.test/start/A000000000000000000000000000000001');

        $this->verify('A000000000000000000000000000000001', 'OK')
            ->assertOk()
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.payment.referenceId', '987654321');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://gateway.test/request'
            && $request['amount'] === 1_200_000
            && $request['merchant_id'] === '00000000-0000-0000-0000-000000000000');

        $attempt = PaymentAttempt::query()->firstOrFail();
        $this->assertSame('[REDACTED]', $attempt->request_payload['merchant_id']);
        $this->assertArrayNotHasKey('card_pan', $attempt->verification_payload['data']);
        $this->assertArrayNotHasKey('card_hash', $attempt->verification_payload['data']);
    }

    private function checkout(string $key, int $quantity)
    {
        return $this->actingAs($this->customer, 'customer')
            ->postJson('/api/checkout', [
                'customer' => [
                    'fullName' => 'سجاد خواص',
                    'mobile' => '09123456780',
                    'province' => 'تهران',
                    'city' => 'تهران',
                    'address' => 'خیابان نمونه، پلاک ۱',
                    'postalCode' => '1234567890',
                ],
                'deliveryMethod' => 'standard',
                'items' => [[
                    'variantId' => $this->variant->public_id,
                    'quantity' => $quantity,
                ]],
            ], [
                'Idempotency-Key' => $key,
                'Origin' => 'http://localhost:5173',
                'Referer' => 'http://localhost:5173/',
            ]);
    }

    private function initiate(string $orderId, string $key)
    {
        return $this->actingAs($this->customer, 'customer')
            ->postJson("/api/orders/{$orderId}/payments", [], [
                'Idempotency-Key' => $key,
                'Origin' => 'http://localhost:5173',
                'Referer' => 'http://localhost:5173/',
            ]);
    }

    private function verify(string $authority, string $status)
    {
        return $this->actingAs($this->customer, 'customer')
            ->postJson('/api/payments/verify', [
                'authority' => $authority,
                'status' => $status,
            ], [
                'Origin' => 'http://localhost:5173',
                'Referer' => 'http://localhost:5173/',
            ]);
    }
}
