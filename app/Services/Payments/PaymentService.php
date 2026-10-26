<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\IdempotencyConflict;
use App\Exceptions\PaymentProviderException;
use App\Exceptions\PaymentUnavailable;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\Commerce\CommerceAuditService;
use App\Services\Commerce\PaymentCallbackService;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

final class PaymentService
{
    public function __construct(
        private readonly PaymentProviderManager $providers,
        private readonly OrderLifecycleService $orders,
        private readonly PaymentCallbackService $callbacks,
        private readonly CommerceAuditService $audit,
    ) {}

    /** @return array{attempt: PaymentAttempt, order: Order, replayed: bool} @throws JsonException */
    public function initiate(Customer $customer, Order $order, string $idempotencyKey): array
    {
        if (! config('lbb.payment.enabled', false) || ! $this->providers->ready()) {
            throw new PaymentUnavailable;
        }
        $provider = $this->providers->current();
        $requestHash = hash('sha256', json_encode([
            'orderId' => $order->public_id, 'provider' => $provider->name(), 'amountToman' => $order->grand_total_toman,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        try {
            $prepared = DB::transaction(function () use ($customer, $order, $idempotencyKey, $requestHash, $provider): array {
                $lockedOrder = Order::query()->ownedBy($customer)->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
                $existing = PaymentAttempt::query()->ownedBy($customer)->where('order_id', $lockedOrder->getKey())
                    ->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                if ($existing) {
                    if (! hash_equals($existing->request_hash, $requestHash)) {
                        throw new IdempotencyConflict;
                    }

                    return ['attempt' => $existing, 'order' => $lockedOrder, 'replayed' => true, 'dispatch' => false];
                }
                $this->assertPayableLocked($lockedOrder);
                $active = PaymentAttempt::query()->where('order_id', $lockedOrder->getKey())
                    ->whereIn('status', [PaymentAttemptStatus::Initiated->value, PaymentAttemptStatus::Pending->value])
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->latest('id')->lockForUpdate()->first();
                if ($active) {
                    return ['attempt' => $active, 'order' => $lockedOrder, 'replayed' => true, 'dispatch' => false];
                }

                $expiresAt = now()->addMinutes(max(1, (int) config('lbb.payment.attempt_ttl_minutes', 20)));
                if ($lockedOrder->reservation_expires_at?->lt($expiresAt)) {
                    $expiresAt = $lockedOrder->reservation_expires_at;
                }
                $attempt = PaymentAttempt::query()->create([
                    'order_id' => $lockedOrder->getKey(), 'customer_id' => $customer->getKey(), 'provider' => $provider->name(),
                    'attempt_number' => ((int) PaymentAttempt::query()->where('order_id', $lockedOrder->getKey())->max('attempt_number')) + 1,
                    'idempotency_key' => $idempotencyKey, 'request_hash' => $requestHash, 'status' => PaymentAttemptStatus::Initiated,
                    'amount_toman' => $lockedOrder->grand_total_toman,
                    'amount_provider' => $lockedOrder->grand_total_toman * max(1, (int) config('lbb.payment.amount_multiplier', 10)),
                    'currency' => (string) config('lbb.payment.currency', 'IRR'), 'expires_at' => $expiresAt,
                ]);
                $lockedOrder->forceFill(['payment_status' => PaymentStatus::Pending])->save();
                $this->audit->record('payment.initiated', 'payment_attempt', $attempt->public_id, $lockedOrder, 'customer', $customer->getKey(), [
                    'provider' => $provider->name(), 'amountToman' => $lockedOrder->grand_total_toman,
                ]);

                return ['attempt' => $attempt, 'order' => $lockedOrder, 'replayed' => false, 'dispatch' => true];
            }, 3);
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw $exception;
            }
            $existing = PaymentAttempt::query()->ownedBy($customer)->where('order_id', $order->getKey())->where('idempotency_key', $idempotencyKey)->first();
            if (! $existing || ! hash_equals($existing->request_hash, $requestHash)) {
                throw $exception;
            }

            return ['attempt' => $existing->load('order'), 'order' => $existing->order, 'replayed' => true];
        }

        if (! $prepared['dispatch']) {
            return ['attempt' => $prepared['attempt']->fresh(), 'order' => $prepared['order']->fresh(['items', 'paymentAttempts', 'shipment']), 'replayed' => true];
        }

        try {
            $result = $provider->initiate($prepared['attempt'], $prepared['order']);
        } catch (Throwable $exception) {
            $this->failInitiation($prepared['attempt'], $exception);
            throw $exception;
        }

        $attempt = DB::transaction(function () use ($prepared, $result): PaymentAttempt {
            $locked = PaymentAttempt::query()->whereKey($prepared['attempt']->getKey())->lockForUpdate()->firstOrFail();
            Order::query()->whereKey($locked->order_id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== PaymentAttemptStatus::Initiated) {
                return $locked;
            }
            $locked->forceFill([
                'status' => PaymentAttemptStatus::Pending, 'authority' => $result->authority, 'redirect_url' => $result->redirectUrl,
                'gateway_code' => $result->gatewayCode, 'request_payload' => $result->requestPayload, 'response_payload' => $result->responsePayload,
            ])->save();

            return $locked;
        }, 3);

        return ['attempt' => $attempt->fresh(), 'order' => $prepared['order']->fresh(['items', 'paymentAttempts', 'shipment']), 'replayed' => false];
    }

    /** @return array{attempt: PaymentAttempt, order: Order, replayed: bool} */
    public function verify(Customer $customer, string $authority, ?string $callbackStatus): array
    {
        $attempt = PaymentAttempt::query()->ownedBy($customer)->where('authority', $authority)->with('order')->firstOrFail();
        $callback = $this->callbacks->receive($attempt, $callbackStatus);
        if ($callback['replayed'] && $callback['event']->processed_at !== null) {
            return ['attempt' => $attempt->fresh(), 'order' => $attempt->order->fresh(['items', 'paymentAttempts', 'shipment']), 'replayed' => true];
        }
        if ($attempt->isVerified()) {
            $this->callbacks->processed($callback['event'], $attempt->reference_id, true);

            return ['attempt' => $attempt, 'order' => $attempt->order->load(['items', 'paymentAttempts', 'shipment']), 'replayed' => true];
        }

        $provider = $this->providers->for($attempt->provider);
        $result = $provider->verify($attempt, $attempt->order, $callbackStatus);
        if (! $result->verified) {
            $failed = DB::transaction(function () use ($attempt, $result): PaymentAttempt {
                $locked = PaymentAttempt::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();
                if ($locked->status === PaymentAttemptStatus::Verified) {
                    return $locked;
                }
                $status = $result->state === 'cancelled' ? PaymentAttemptStatus::Cancelled : PaymentAttemptStatus::Failed;
                $locked->forceFill([
                    'status' => $status, 'gateway_code' => $result->gatewayCode, 'failure_code' => $result->failureCode,
                    'failure_message' => $result->message, 'verification_payload' => $result->payload, 'failed_at' => now(),
                    'cancelled_at' => $status === PaymentAttemptStatus::Cancelled ? now() : null,
                ])->save();
                $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->firstOrFail();
                if ($order->payment_status !== PaymentStatus::Paid && $order->status === OrderStatus::AwaitingPayment) {
                    $order->forceFill(['payment_status' => PaymentStatus::Failed])->save();
                }

                return $locked;
            }, 3);
            $this->callbacks->processed($callback['event'], $result->referenceId, false);
            $this->audit->record('payment.rejected', 'payment_attempt', $failed->public_id, $attempt->order, 'provider', null, ['providerCode' => $result->failureCode]);

            return ['attempt' => $failed->fresh(), 'order' => $attempt->order->fresh(['items', 'paymentAttempts', 'shipment']), 'replayed' => false];
        }

        $verified = DB::transaction(function () use ($attempt, $result): PaymentAttempt {
            $lockedAttempt = PaymentAttempt::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedAttempt->status === PaymentAttemptStatus::Verified) {
                return $lockedAttempt;
            }
            $lockedOrder = Order::query()->whereKey($lockedAttempt->order_id)->lockForUpdate()->firstOrFail();
            if ((int) $lockedAttempt->amount_toman !== (int) $lockedOrder->grand_total_toman) {
                throw new PaymentProviderException('مبلغ تلاش پرداخت با مبلغ سفارش تطابق ندارد.', 'amount_mismatch');
            }
            $this->orders->markPaidFromVerifiedPaymentLocked($lockedOrder);
            $lockedAttempt->forceFill([
                'status' => PaymentAttemptStatus::Verified, 'reference_id' => $result->referenceId, 'gateway_code' => $result->gatewayCode,
                'failure_code' => null, 'failure_message' => null, 'verification_payload' => $result->payload, 'verified_at' => now(),
            ])->save();

            return $lockedAttempt;
        }, 3);
        $this->callbacks->processed($callback['event'], $result->referenceId, true);
        $this->audit->record('payment.verified', 'payment_attempt', $verified->public_id, $attempt->order, 'provider', null, ['provider' => $attempt->provider]);

        return ['attempt' => $verified->fresh(), 'order' => $attempt->order->fresh(['items', 'paymentAttempts', 'shipment']), 'replayed' => false];
    }

    private function assertPayableLocked(Order $order): void
    {
        if ($order->status !== OrderStatus::AwaitingPayment) {
            throw ValidationException::withMessages(['order' => ['این سفارش در وضعیت فعلی قابل پرداخت نیست.']]);
        }
        if ($order->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages(['order' => ['این سفارش قبلاً پرداخت شده است.']]);
        }
        if (! $order->reservation_expires_at || $order->reservation_expires_at->isPast()) {
            throw ValidationException::withMessages(['order' => ['مهلت رزرو موجودی این سفارش پایان یافته است.']]);
        }
    }

    private function failInitiation(PaymentAttempt $attempt, Throwable $exception): void
    {
        DB::transaction(function () use ($attempt, $exception): void {
            $locked = PaymentAttempt::query()->whereKey($attempt->getKey())->lockForUpdate()->first();
            if (! $locked || $locked->status !== PaymentAttemptStatus::Initiated) {
                return;
            }
            $locked->forceFill([
                'status' => PaymentAttemptStatus::Failed,
                'failure_code' => $exception instanceof PaymentProviderException ? ($exception->providerCode ?: 'provider_error') : 'provider_unavailable',
                'failure_message' => mb_substr($exception->getMessage(), 0, 500), 'failed_at' => now(),
            ])->save();
            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->first();
            if ($order && $order->payment_status !== PaymentStatus::Paid) {
                $order->forceFill(['payment_status' => PaymentStatus::Failed])->save();
            }
            if ($order) {
                $this->audit->record('payment.initiation_failed', 'payment_attempt', $locked->public_id, $order, 'provider');
            }
        }, 3);
    }
}
