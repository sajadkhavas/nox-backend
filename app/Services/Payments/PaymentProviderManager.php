<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProvider;
use App\Exceptions\PaymentUnavailable;
use App\Services\Payments\Providers\DisabledPaymentProvider;
use App\Services\Payments\Providers\TestingPaymentProvider;
use App\Services\Payments\Providers\ZarinpalPaymentProvider;

final class PaymentProviderManager
{
    public function current(): PaymentProvider
    {
        return $this->for((string) config('lbb.payment.provider', 'disabled'));
    }

    public function for(string $provider): PaymentProvider
    {
        $provider = strtolower(trim($provider));

        return match ($provider) {
            'disabled' => app(DisabledPaymentProvider::class),
            'testing' => app(TestingPaymentProvider::class),
            'zarinpal' => app(ZarinpalPaymentProvider::class),
            default => throw new PaymentUnavailable("Payment provider ناشناخته است: {$provider}"),
        };
    }

    public function ready(): bool
    {
        if (! config('lbb.payment.enabled', false)) {
            return false;
        }

        $provider = strtolower(trim((string) config('lbb.payment.provider', 'disabled')));
        if ($provider === 'disabled') {
            return false;
        }

        if ($provider === 'testing') {
            return ! app()->environment('production');
        }

        if ($provider === 'zarinpal') {
            return trim((string) config('lbb.payment.zarinpal.merchant_id')) !== '';
        }

        return false;
    }
}
