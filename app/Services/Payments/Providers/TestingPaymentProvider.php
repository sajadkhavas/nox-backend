<?php

namespace App\Services\Payments\Providers;

use App\Contracts\Payments\PaymentProvider;
use App\Exceptions\PaymentUnavailable;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\Payments\Data\PaymentInitiationResult;
use App\Services\Payments\Data\PaymentVerificationResult;
use Illuminate\Support\Str;

final class TestingPaymentProvider implements PaymentProvider
{
    public function name(): string
    {
        return 'testing';
    }

    public function initiate(PaymentAttempt $attempt, Order $order): PaymentInitiationResult
    {
        if (app()->environment('production')) {
            throw new PaymentUnavailable('درگاه آزمایشی در محیط production مجاز نیست.');
        }

        $authority = 'TEST-'.Str::upper($attempt->public_id);
        $callbackUrl = (string) config('lbb.payment.callback_url');
        $redirectUrl = $callbackUrl.(str_contains($callbackUrl, '?') ? '&' : '?').http_build_query([
            'Status' => 'OK',
            'Authority' => $authority,
            'provider' => 'testing',
        ]);

        return new PaymentInitiationResult(
            authority: $authority,
            redirectUrl: $redirectUrl,
            gatewayCode: 'TEST-100',
            requestPayload: [
                'provider' => 'testing',
                'orderNumber' => $order->order_number,
                'amount' => $attempt->amount_provider,
            ],
            responsePayload: [
                'authority' => $authority,
                'code' => 100,
            ],
        );
    }

    public function verify(
        PaymentAttempt $attempt,
        Order $order,
        ?string $callbackStatus,
    ): PaymentVerificationResult {
        if (app()->environment('production')) {
            throw new PaymentUnavailable('درگاه آزمایشی در محیط production مجاز نیست.');
        }

        $status = Str::upper(trim((string) $callbackStatus));
        if ($status !== 'OK') {
            return PaymentVerificationResult::failed(
                state: 'cancelled',
                failureCode: 'customer_cancelled',
                message: 'پرداخت آزمایشی توسط کاربر لغو شد.',
                gatewayCode: 'TEST-NOK',
                payload: ['status' => $status],
            );
        }

        return PaymentVerificationResult::verified(
            referenceId: 'TESTREF-'.$attempt->public_id,
            gatewayCode: 'TEST-100',
            payload: [
                'status' => 'OK',
                'authority' => $attempt->authority,
                'amount' => $attempt->amount_provider,
            ],
        );
    }
}
