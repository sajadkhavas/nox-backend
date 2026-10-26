<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\NotificationOutbox;
use App\Models\Order;
use App\Models\PushSubscription;
use App\Services\Notifications\NotificationOutboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebPushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'session.driver' => 'array',
            'lbb.web_push.enabled' => true,
            'lbb.web_push.vapid.subject' => 'https://lbb.example.test',
            'lbb.web_push.vapid.public_key' => 'BPublicKeyForContractOnly',
            'lbb.web_push.vapid.private_key' => 'PrivateKeyMustNeverBeReturned',
        ]);
    }

    public function test_public_configuration_never_exposes_private_vapid_key(): void
    {
        $response = $this->getJson('/api/web-push/config')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.publicKey', 'BPublicKeyForContractOnly');

        $this->assertStringNotContainsString('PrivateKeyMustNeverBeReturned', $response->getContent());
    }

    public function test_authenticated_customer_can_register_and_revoke_an_encrypted_subscription(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer, 'customer');
        $endpoint = 'https://push.example.test/subscriptions/device-1';
        $p256dh = str_repeat('p', 88);
        $auth = str_repeat('a', 24);

        $this->postJson('/api/web-push/subscriptions', [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => $p256dh, 'auth' => $auth],
            'contentEncoding' => 'aes128gcm',
        ])->assertCreated()
            ->assertJsonPath('data.activeCount', 1)
            ->assertJsonMissing(['endpoint' => $endpoint]);

        $subscription = PushSubscription::query()->firstOrFail();
        $this->assertSame($endpoint, $subscription->endpoint);
        $this->assertSame($p256dh, $subscription->p256dh);
        $this->assertSame($auth, $subscription->auth_token);

        $raw = DB::table('push_subscriptions')->first();
        $this->assertNotSame($endpoint, $raw->endpoint);
        $this->assertNotSame($p256dh, $raw->p256dh);
        $this->assertNotSame($auth, $raw->auth_token);

        $this->getJson('/api/web-push/subscriptions')
            ->assertOk()
            ->assertJsonPath('data.activeCount', 1);

        $this->deleteJson('/api/web-push/subscriptions', ['endpoint' => $endpoint])
            ->assertOk()
            ->assertJsonPath('data.activeCount', 0);

        $this->assertNotNull($subscription->fresh()->revoked_at);
    }

    public function test_order_notification_outbox_queues_web_push_for_each_active_customer_subscription(): void
    {
        $customer = $this->customer('09123456782');
        $subscription = $customer->pushSubscriptions()->create([
            'endpoint_hash' => PushSubscription::endpointHash('https://push.example.test/subscriptions/order-device'),
            'endpoint' => 'https://push.example.test/subscriptions/order-device',
            'p256dh' => str_repeat('p', 88),
            'auth_token' => str_repeat('a', 24),
            'content_encoding' => 'aes128gcm',
            'last_seen_at' => now(),
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->getKey(),
            'order_number' => 'LBB-PUSH-0001',
            'idempotency_key' => 'push-order-test-0001',
            'request_hash' => hash('sha256', 'push-order-test-0001'),
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'delivery_method' => DeliveryMethod::Pickup,
            'subtotal_toman' => 100_000,
            'delivery_fee_toman' => 0,
            'packaging_fee_toman' => 0,
            'discount_total_toman' => 0,
            'grand_total_toman' => 100_000,
            'item_count' => 1,
            'customer_name' => 'مشتری پوش',
            'customer_mobile' => $customer->mobile,
            'placed_at' => now(),
            'paid_at' => now(),
        ]);

        app(NotificationOutboxService::class)->queueOrder($order, 'order.paid');

        $this->assertDatabaseHas('notification_outbox', [
            'customer_id' => $customer->getKey(),
            'order_id' => $order->getKey(),
            'channel' => 'sms',
            'template_key' => 'order.paid',
        ]);
        $this->assertDatabaseHas('notification_outbox', [
            'customer_id' => $customer->getKey(),
            'order_id' => $order->getKey(),
            'channel' => 'web_push',
            'template_key' => 'order.paid',
            'provider' => 'web-push',
        ]);

        $pushOutbox = NotificationOutbox::query()
            ->where('customer_id', $customer->getKey())
            ->where('order_id', $order->getKey())
            ->where('channel', 'web_push')
            ->firstOrFail();

        $this->assertSame($subscription->public_id, $pushOutbox->destination);
        $this->assertNotSame(
            $subscription->public_id,
            DB::table('notification_outbox')->where('id', $pushOutbox->getKey())->value('destination'),
        );
        $this->assertDatabaseCount('notification_outbox', 2);
    }

    public function test_subscription_mutations_require_customer_authentication(): void
    {
        $payload = [
            'endpoint' => 'https://push.example.test/subscriptions/device-2',
            'keys' => ['p256dh' => str_repeat('p', 88), 'auth' => str_repeat('a', 24)],
        ];

        $this->postJson('/api/web-push/subscriptions', $payload)->assertUnauthorized();
        $this->deleteJson('/api/web-push/subscriptions', ['endpoint' => $payload['endpoint']])->assertUnauthorized();
        $this->postJson('/api/web-push/test')->assertUnauthorized();
    }

    public function test_disabled_push_is_fail_closed(): void
    {
        config(['lbb.web_push.enabled' => false]);
        $customer = $this->customer('09123456781');
        $this->actingAs($customer, 'customer');

        $this->getJson('/api/web-push/config')
            ->assertOk()
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.publicKey', null);

        $this->postJson('/api/web-push/test')->assertServiceUnavailable();
    }

    private function customer(string $mobile = '09123456780'): Customer
    {
        return Customer::query()->create([
            'mobile' => $mobile,
            'mobile_verified_at' => now(),
            'is_active' => true,
        ]);
    }
}
