<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\NotificationStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\NotificationOutbox;
use App\Models\Order;
use App\Services\Notifications\NotificationOutboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationOutboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_provider_keeps_encrypted_notifications_pending_until_activation(): void
    {
        config(['lbb.notifications.sms_provider' => 'disabled']);
        $order = $this->createOrder();
        $notification = app(NotificationOutboxService::class)->queueOrder($order, 'order.ready');
        $rawDestination = DB::table('notification_outbox')
            ->where('id', $notification->getKey())
            ->value('destination');

        $this->assertNotSame('09120000000', $rawDestination);
        $this->assertSame(0, app(NotificationOutboxService::class)->dispatchPending());

        $notification->refresh();
        $this->assertSame(NotificationStatus::Pending, $notification->status);
        $this->assertSame(0, $notification->attempts);
        $this->assertNull($notification->last_error);
    }

    public function test_testing_provider_dispatches_from_outbox_without_external_credentials(): void
    {
        config(['lbb.notifications.sms_provider' => 'testing']);
        $order = $this->createOrder();
        app(NotificationOutboxService::class)->queueOrder($order, 'order.dispatched', [
            'tracking_code' => 'TEST-TRACK',
        ]);

        $this->assertSame(1, app(NotificationOutboxService::class)->dispatchPending());

        $notification = NotificationOutbox::query()->firstOrFail();
        $this->assertSame(NotificationStatus::Sent, $notification->status);
        $this->assertSame('testing', $notification->provider);
        $this->assertSame(1, $notification->attempts);
        $this->assertNotNull($notification->provider_message_id);
        $this->assertNotNull($notification->sent_at);
    }

    private function createOrder(): Order
    {
        $customer = Customer::query()->create([
            'mobile' => '09120000000',
            'mobile_verified_at' => now(),
            'is_active' => true,
        ]);

        return Order::query()->create([
            'customer_id' => $customer->getKey(),
            'order_number' => 'LBB-NOTIFY-0001',
            'idempotency_key' => 'notification-order-idempotency-key',
            'request_hash' => hash('sha256', 'notification-order'),
            'status' => OrderStatus::Ready,
            'payment_status' => PaymentStatus::Paid,
            'delivery_method' => DeliveryMethod::Pickup,
            'subtotal_toman' => 100_000,
            'delivery_fee_toman' => 0,
            'packaging_fee_toman' => 0,
            'discount_total_toman' => 0,
            'grand_total_toman' => 100_000,
            'item_count' => 1,
            'preparation_time_days' => 1,
            'preparation_max_days' => 1,
            'customer_name' => 'مشتری اعلان',
            'customer_mobile' => '09120000000',
            'placed_at' => now(),
            'paid_at' => now(),
            'ready_at' => now(),
        ]);
    }
}
