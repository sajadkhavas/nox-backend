<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentFilamentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_payment_list_and_read_only_view(): void
    {
        Role::create([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $admin = User::create([
            'name' => 'LBB Admin',
            'email' => 'payment-admin@example.test',
            'password' => 'payment-test-password',
        ]);
        $admin->assignRole('super_admin');

        $customer = Customer::query()->create([
            'mobile' => '09120000000',
            'mobile_verified_at' => now(),
        ]);

        $order = Order::query()->create([
            'customer_id' => $customer->getKey(),
            'order_number' => 'LBB-PAY-0001',
            'idempotency_key' => 'filament-payment-order-key',
            'request_hash' => hash('sha256', 'filament-payment-order'),
            'status' => OrderStatus::AwaitingPayment,
            'payment_status' => PaymentStatus::Pending,
            'delivery_method' => DeliveryMethod::Pickup,
            'subtotal_toman' => 100_000,
            'delivery_fee_toman' => 0,
            'packaging_fee_toman' => 0,
            'discount_total_toman' => 0,
            'grand_total_toman' => 100_000,
            'item_count' => 1,
            'preparation_time_days' => 1,
            'customer_name' => 'مشتری تست',
            'customer_mobile' => '09120000000',
            'reservation_expires_at' => now()->addMinutes(20),
            'placed_at' => now(),
        ]);

        $attempt = PaymentAttempt::query()->create([
            'order_id' => $order->getKey(),
            'customer_id' => $customer->getKey(),
            'provider' => 'testing',
            'attempt_number' => 1,
            'idempotency_key' => 'filament-payment-attempt-key',
            'request_hash' => hash('sha256', 'filament-payment-attempt'),
            'status' => PaymentAttemptStatus::Pending,
            'amount_toman' => 100_000,
            'amount_provider' => 1_000_000,
            'currency' => 'IRR',
            'authority' => 'TEST-FILAMENT-PAYMENT',
            'redirect_url' => 'https://example.test/payment',
        ]);

        $this->actingAs($admin)
            ->get('/admin/payment-attempts')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/admin/payment-attempts/{$attempt->public_id}")
            ->assertOk();
    }
}
