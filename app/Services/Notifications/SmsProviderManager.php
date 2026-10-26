<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\SmsProvider;
use App\Exceptions\NotificationDeliveryUnavailable;
use App\Services\Notifications\Providers\DisabledSmsProvider;
use App\Services\Notifications\Providers\KavenegarSmsProvider;
use App\Services\Notifications\Providers\TestingSmsProvider;

final class SmsProviderManager
{
    public function current(): SmsProvider
    {
        return match (strtolower(trim((string) config('lbb.notifications.sms_provider', 'disabled')))) {
            'disabled' => app(DisabledSmsProvider::class),
            'testing' => app(TestingSmsProvider::class),
            'kavenegar' => app(KavenegarSmsProvider::class),
            default => throw new NotificationDeliveryUnavailable('ارائه‌دهنده پیامک ناشناخته است.'),
        };
    }

    public function ready(): bool
    {
        $provider = strtolower(trim((string) config('lbb.notifications.sms_provider', 'disabled')));

        return match ($provider) {
            'testing' => ! app()->environment('production'),
            'kavenegar' => trim((string) config('lbb.notifications.kavenegar.api_key')) !== '',
            default => false,
        };
    }
}
