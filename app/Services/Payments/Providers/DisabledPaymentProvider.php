<?php

namespace App\Services\Payments\Providers;

use App\Contracts\Payments\PaymentProvider;
use App\Exceptions\PaymentUnavailable;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\Payments\Data\PaymentInitiationResult;
use App\Services\Payments\Data\PaymentVerificationResult;

final class DisabledPaymentProvider implements PaymentProvider
{
    public function name(): string
    {
        return 'disabled';
    }

    public function initiate(PaymentAttempt $attempt, Order $order): PaymentInitiationResult
    {
        throw new PaymentUnavailable;
    }

    public function verify(
        PaymentAttempt $attempt,
        Order $order,
        ?string $callbackStatus,
    ): PaymentVerificationResult {
        throw new PaymentUnavailable;
    }
}
