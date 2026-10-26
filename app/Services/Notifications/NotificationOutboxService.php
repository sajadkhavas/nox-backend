<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\NotificationOutbox;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class NotificationOutboxService
{
    public function __construct(
        private readonly SmsProviderManager $providers,
        private readonly WebPushService $webPush,
    ) {}

    public function queueOrder(Order $order, string $templateKey, array $payload = []): NotificationOutbox
    {
        $notificationPayload = [
            'order_number' => $order->order_number,
            'tracking_code' => $order->tracking_code,
            ...$payload,
        ];

        $sms = NotificationOutbox::query()->create([
            'customer_id' => $order->customer_id,
            'order_id' => $order->getKey(),
            'channel' => NotificationChannel::Sms,
            'destination' => $order->customer_mobile,
            'template_key' => $templateKey,
            'payload' => $notificationPayload,
            'status' => NotificationStatus::Pending,
            'provider' => strtolower(trim((string) config('lbb.notifications.sms_provider', 'disabled'))),
            'available_at' => now(),
        ]);

        if ($order->customer_id) {
            PushSubscription::query()
                ->where('customer_id', $order->customer_id)
                ->active()
                ->get()
                ->each(function (PushSubscription $subscription) use ($order, $templateKey, $notificationPayload): void {
                    NotificationOutbox::query()->create([
                        'customer_id' => $order->customer_id,
                        'order_id' => $order->getKey(),
                        'channel' => NotificationChannel::WebPush,
                        'destination' => $subscription->public_id,
                        'template_key' => $templateKey,
                        'payload' => $notificationPayload,
                        'status' => NotificationStatus::Pending,
                        'provider' => 'web-push',
                        'available_at' => now(),
                    ]);
                });
        }

        return $sms;
    }

    public function dispatchPending(int $limit = 50): int
    {
        $channels = [];
        if ($this->providers->ready()) {
            $channels[] = NotificationChannel::Sms->value;
        }
        if ($this->webPush->ready()) {
            $channels[] = NotificationChannel::WebPush->value;
        }
        if ($channels === []) {
            return 0;
        }

        NotificationOutbox::query()
            ->where('status', NotificationStatus::Processing->value)
            ->whereIn('channel', $channels)
            ->where('updated_at', '<=', now()->subMinutes(10))
            ->update([
                'status' => NotificationStatus::Pending->value,
                'available_at' => now(),
                'last_error' => 'stale_processing_recovered',
                'updated_at' => now(),
            ]);

        $ids = NotificationOutbox::query()
            ->ready()
            ->whereIn('channel', $channels)
            ->orderBy('id')
            ->limit(max(1, min(500, $limit)))
            ->pluck('id');
        $sent = 0;

        foreach ($ids as $id) {
            if ($this->dispatchOne((int) $id)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function dispatchOne(int $id): bool
    {
        $candidate = NotificationOutbox::query()->find($id);
        if (! $candidate || ! $this->channelReady($candidate->channel)) {
            return false;
        }

        $notification = DB::transaction(function () use ($id): ?NotificationOutbox {
            $locked = NotificationOutbox::query()->whereKey($id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== NotificationStatus::Pending || ! $this->channelReady($locked->channel)) {
                return null;
            }

            $locked->forceFill([
                'status' => NotificationStatus::Processing,
                'attempts' => $locked->attempts + 1,
                'provider' => $locked->channel === NotificationChannel::Sms
                    ? strtolower(trim((string) config('lbb.notifications.sms_provider', 'disabled')))
                    : 'web-push',
            ])->save();

            return $locked->fresh();
        }, 3);

        if (! $notification) {
            return false;
        }

        try {
            return match ($notification->channel) {
                NotificationChannel::Sms => $this->dispatchSms($notification),
                NotificationChannel::WebPush => $this->dispatchWebPush($notification),
            };
        } catch (Throwable $exception) {
            $this->recordFailure($notification, $exception);

            return false;
        }
    }

    private function dispatchSms(NotificationOutbox $notification): bool
    {
        $template = NotificationTemplate::query()
            ->where('key', $notification->template_key)
            ->where('channel', NotificationChannel::Sms->value)
            ->where('is_active', true)
            ->first();
        if (! $template) {
            throw new RuntimeException('قالب اعلان فعال پیدا نشد.');
        }

        $provider = $this->providers->current();
        $providerMessageId = $provider->send(
            $notification->destination,
            $template->render($notification->payload ?? []),
            $template->provider_template,
        );
        $this->recordSent($notification, $provider->name(), $providerMessageId);

        return true;
    }

    private function dispatchWebPush(NotificationOutbox $notification): bool
    {
        $subscription = PushSubscription::query()
            ->where('public_id', $notification->destination)
            ->active()
            ->first();
        if (! $subscription) {
            $this->recordTerminalFailure($notification, 'push_subscription_missing_or_revoked');

            return false;
        }

        if ($notification->template_key === 'campaign' &&
            (! $subscription->marketing_enabled ||
             ($notification->payload['topic'] ?? '') !== 'broadcast' &&
             ! in_array($notification->payload['topic'] ?? '', $subscription->preferences ?? [], true))) {
            $this->recordTerminalFailure($notification, 'push_consent_withdrawn');

            return false;
        }

        $result = $this->webPush->sendSubscription(
            $subscription,
            $notification->template_key === 'campaign'
                ? $this->webPush->campaignPayload($notification->payload ?? [])
                : $this->webPush->orderPayload($notification->template_key, $notification->payload ?? []),
        );
        if ($result === null) {
            $this->recordTerminalFailure($notification, 'push_subscription_expired');

            return false;
        }
        if ($result !== true) {
            throw new RuntimeException('ارسال Web Push توسط سرویس مقصد ناموفق بود.');
        }

        $this->recordSent($notification, 'web-push', null);

        return true;
    }

    private function channelReady(NotificationChannel $channel): bool
    {
        return match ($channel) {
            NotificationChannel::Sms => $this->providers->ready(),
            NotificationChannel::WebPush => $this->webPush->ready(),
        };
    }

    private function recordSent(NotificationOutbox $notification, string $provider, ?string $providerMessageId): void
    {
        DB::transaction(function () use ($notification, $provider, $providerMessageId): void {
            $locked = NotificationOutbox::query()->whereKey($notification->getKey())->lockForUpdate()->firstOrFail();
            $locked->forceFill([
                'status' => NotificationStatus::Sent,
                'provider' => $provider,
                'provider_message_id' => $providerMessageId,
                'last_error' => null,
                'sent_at' => now(),
                'failed_at' => null,
            ])->save();
        }, 3);
    }

    private function recordTerminalFailure(NotificationOutbox $notification, string $reason): void
    {
        DB::transaction(function () use ($notification, $reason): void {
            $locked = NotificationOutbox::query()->whereKey($notification->getKey())->lockForUpdate()->first();
            if (! $locked || $locked->status === NotificationStatus::Sent) {
                return;
            }
            $locked->forceFill([
                'status' => NotificationStatus::Failed,
                'last_error' => $reason,
                'available_at' => null,
                'failed_at' => now(),
            ])->save();
        }, 3);
    }

    private function recordFailure(NotificationOutbox $notification, Throwable $exception): void
    {
        DB::transaction(function () use ($notification, $exception): void {
            $locked = NotificationOutbox::query()->whereKey($notification->getKey())->lockForUpdate()->first();
            if (! $locked || $locked->status === NotificationStatus::Sent) {
                return;
            }

            $maximum = max(1, (int) config('lbb.notifications.max_attempts', 5));
            $terminal = $locked->attempts >= $maximum;
            $locked->forceFill([
                'status' => $terminal ? NotificationStatus::Failed : NotificationStatus::Pending,
                'last_error' => mb_substr($exception->getMessage(), 0, 1000),
                'available_at' => $terminal
                    ? null
                    : now()->addSeconds(max(30, (int) config('lbb.notifications.retry_seconds', 60)) * $locked->attempts),
                'failed_at' => $terminal ? now() : null,
            ])->save();
        }, 3);
    }
}
