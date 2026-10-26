<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderFilamentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_order_list_and_read_only_view(): void
    {
        Role::create([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $admin = User::create([
            'name' => 'LBB Admin',
            'email' => 'orders-admin@example.test',
            'password' => 'order-test-password',
        ]);
        $admin->assignRole('super_admin');

        $customer = Customer::query()->create([
            'mobile' => '09120000000',
            'mobile_verified_at' => now(),
        ]);

        $order = Order::query()->create([
            'customer_id' => $customer->getKey(),
            'order_number' => 'LBB-TEST-0001',
            'idempotency_key' => 'filament-order-key-0001',
            'request_hash' => hash('sha256', 'filament-order'),
            'status' => OrderStatus::AwaitingPayment,
            'payment_status' => PaymentStatus::Unpaid,
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

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/admin/orders/{$order->public_id}")
            ->assertOk();
    }
}
