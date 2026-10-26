<?php

namespace App\Services\Notifications;

use App\Models\Customer;
use App\Models\PushSubscription as StoredPushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use RuntimeException;

final class WebPushService
{
    public function ready(): bool
    {
        return (bool) config('lbb.web_push.enabled', false)
            && trim((string) config('lbb.web_push.vapid.public_key')) !== ''
            && trim((string) config('lbb.web_push.vapid.private_key')) !== ''
            && trim((string) config('lbb.web_push.vapid.subject')) !== '';
    }

    public function publicKey(): ?string
    {
        $key = trim((string) config('lbb.web_push.vapid.public_key'));

        return $key === '' ? null : $key;
    }

    /** @return array{sent:int,failed:int,revoked:int} */
    public function sendToCustomer(Customer $customer, array $payload): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'revoked' => 0];
        if (! $this->ready()) {
            return $result;
        }

        foreach ($customer->pushSubscriptions()->active()->get() as $subscription) {
            $sent = $this->sendSubscription($subscription, $payload);
            if ($sent === true) {
                $result['sent']++;
            } elseif ($sent === null) {
                $result['revoked']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    public function sendSubscription(StoredPushSubscription $stored, array $payload): ?bool
    {
        if (! $this->ready() || $stored->revoked_at !== null) {
            return false;
        }

        $subscription = Subscription::create([
            'endpoint' => $stored->endpoint,
            'keys' => [
                'p256dh' => $stored->p256dh,
                'auth' => $stored->auth_token,
            ],
            'contentEncoding' => $stored->content_encoding ?: 'aes128gcm',
        ]);

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => (string) config('lbb.web_push.vapid.subject'),
                'publicKey' => (string) config('lbb.web_push.vapid.public_key'),
                'privateKey' => (string) config('lbb.web_push.vapid.private_key'),
            ],
        ], [
            'TTL' => max(60, (int) config('lbb.web_push.ttl_seconds', 21600)),
            'urgency' => (string) config('lbb.web_push.urgency', 'normal'),
            'contentType' => 'application/json',
        ]);
        $webPush->setReuseVAPIDHeaders(true);

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $report = $webPush->sendOneNotification($subscription, $json);

        if ($report->isSuccess()) {
            $stored->forceFill(['last_seen_at' => now()])->save();

            return true;
        }

        if ($report->isSubscriptionExpired()) {
            $stored->forceFill(['revoked_at' => now()])->save();

            return null;
        }

        return false;
    }

    public function orderPayload(string $templateKey, array $payload): array
    {
        $number = trim((string) ($payload['order_number'] ?? ''));
        $tracking = trim((string) ($payload['tracking_code'] ?? ''));
        $orderLabel = $number === '' ? 'سفارش شما' : "سفارش {$number}";

        [$title, $body] = match ($templateKey) {
            'order.paid' => ['پرداخت تأیید شد', "پرداخت {$orderLabel} با موفقیت تأیید شد."],
            'order.preparing' => ['سفارش در حال آماده‌سازی است', "{$orderLabel} وارد مرحله آماده‌سازی شد."],
            'order.ready' => ['سفارش آماده است', "{$orderLabel} آماده مرحله بعدی تحویل است."],
            'order.dispatched' => [
                'سفارش ارسال شد',
                $tracking === ''
                    ? "{$orderLabel} ارسال شد."
                    : "{$orderLabel} ارسال شد. کد پیگیری: {$tracking}",
            ],
            'order.delivered' => ['سفارش تحویل شد', "{$orderLabel} به وضعیت تحویل‌شده رسید."],
            'order.cancelled' => ['سفارش لغو شد', "{$orderLabel} لغو شد."],
            default => throw new RuntimeException('قالب Web Push سفارش شناخته‌شده نیست.'),
        };

        return [
            'title' => $title,
            'body' => $body,
            'icon' => '/icons/icon-192.png',
            'badge' => '/icons/icon-192.png',
            'url' => '/account',
            'tag' => $number === '' ? "lbb-{$templateKey}" : "lbb-order-{$number}",
            'data' => [
                'kind' => 'order',
                'template' => $templateKey,
                'orderNumber' => $number === '' ? null : $number,
            ],
        ];
    }

    public function campaignPayload(array $payload): array
    {
        $url = (string) ($payload['url'] ?? '/');
        if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            throw new RuntimeException('مسیر اعلان باید داخلی باشد.');
        }

        return [
            'title' => (string) ($payload['title'] ?? ''),
            'body' => (string) ($payload['body'] ?? ''),
            'icon' => '/icons/icon-192.png',
            'badge' => '/icons/icon-192.png',
            'url' => $url,
            'tag' => 'lbb-campaign-'.(string) ($payload['campaign_id'] ?? ''),
            'data' => ['kind' => 'campaign', 'topic' => $payload['topic'] ?? null],
        ];
    }
}
