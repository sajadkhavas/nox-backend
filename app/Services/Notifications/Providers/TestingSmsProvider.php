<?php

namespace App\Services\Notifications\Providers;

use App\Contracts\Notifications\SmsProvider;
use App\Exceptions\NotificationDeliveryUnavailable;

final class TestingSmsProvider implements SmsProvider
{
    public function name(): string
    {
        return 'testing';
    }

    public function send(string $destination, string $message, ?string $providerTemplate = null): ?string
    {
        if (app()->environment('production')) {
            throw new NotificationDeliveryUnavailable;
        }

        return 'test-'.substr(hash('sha256', $destination.'|'.$message), 0, 24);
    }
}
