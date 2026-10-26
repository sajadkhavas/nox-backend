<?php

namespace App\Services\Commerce;

use App\Enums\CommerceErrorCode;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\CommerceException;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;

final class RefundService
{
    public function __construct(private readonly CommerceAuditService $audit) {}

    public function requestForReturn(ReturnRequest $return, int $amountToman, ?int $adminId): RefundRequest
    {
        return $this->request(
            $return->order,
            $amountToman,
            "return:{$return->public_id}",
            'مرجوعی تأییدشده نیازمند بازپرداخت است.',
            $return,
            $adminId,
        );
    }

    public function requestForCancellation(Order $order, ?int $adminId, ?string $reason = null): RefundRequest
    {
        return $this->request(
            $order,
            (int) $order->grand_total_toman,
            "cancel:{$order->public_id}",
            $reason ?: 'لغو سفارش پرداخت‌شده نیازمند بازپرداخت است.',
            null,
            $adminId,
        );
    }

    public function request(
        Order $order,
        int $amountToman,
        string $idempotencyKey,
        string $reason,
        ?ReturnRequest $return = null,
        ?int $adminId = null,
    ): RefundRequest {
        if ($amountToman < 1 || $amountToman > (int) $order->grand_total_toman) {
            throw new CommerceException(CommerceErrorCode::RefundUnavailable, 'مبلغ بازپرداخت معتبر نیست.', 422);
        }

        return DB::transaction(function () use ($order, $amountToman, $idempotencyKey, $reason, $return, $adminId): RefundRequest {
            $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            if (! in_array($lockedOrder->payment_status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true)) {
                throw new CommerceException(CommerceErrorCode::RefundUnavailable, 'سفارش پرداخت معتبر برای بازپرداخت ندارد.', 409);
            }

            $existing = RefundRequest::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $nonFailed = (int) RefundRequest::query()
                ->where('order_id', $lockedOrder->getKey())
                ->whereNotIn('status', [RefundStatus::Failed->value])
                ->sum('amount_toman');
            if ($nonFailed + $amountToman > (int) $lockedOrder->grand_total_toman) {
                throw new CommerceException(CommerceErrorCode::RefundUnavailable, 'جمع درخواست‌های بازپرداخت از مبلغ سفارش بیشتر می‌شود.', 409);
            }

            $attempt = $lockedOrder->paymentAttempts()->whereNotNull('verified_at')->latest('verified_at')->first();
            $refund = RefundRequest::query()->create([
                'customer_id' => $lockedOrder->customer_id,
                'order_id' => $lockedOrder->getKey(),
                'return_request_id' => $return?->getKey(),
                'payment_attempt_id' => $attempt?->getKey(),
                'idempotency_key' => $idempotencyKey,
                'request_hash' => hash('sha256', $lockedOrder->public_id.'|'.$amountToman.'|'.$reason),
                'status' => RefundStatus::Requested,
                'amount_toman' => $amountToman,
                'currency' => 'TOMAN',
                'provider' => $attempt?->provider,
                'reason' => $reason,
                'requested_at' => now(),
            ]);
            $this->audit->record('refund.requested', 'refund_request', $refund->public_id, $lockedOrder, 'admin', $adminId, [
                'amountToman' => $amountToman,
                'provider' => $refund->provider,
            ]);

            return $refund;
        }, 3);
    }

    public function markPending(RefundRequest $refund, ?int $adminId = null): RefundRequest
    {
        return DB::transaction(function () use ($refund, $adminId): RefundRequest {
            $locked = RefundRequest::query()->whereKey($refund->getKey())->lockForUpdate()->with('order')->firstOrFail();
            if ($locked->status !== RefundStatus::Requested) {
                throw new CommerceException(CommerceErrorCode::InvalidTransition, 'بازپرداخت در وضعیت قابل شروع نیست.', 409);
            }
            if (! config('lbb.payment.refunds_enabled', false)) {
                throw new CommerceException(CommerceErrorCode::RefundUnavailable, 'Provider بازپرداخت واقعی پیکربندی نشده است.', 503);
            }
            $locked->forceFill(['status' => RefundStatus::Pending])->save();
            $this->audit->record('refund.pending', 'refund_request', $locked->public_id, $locked->order, 'admin', $adminId);

            return $locked;
        }, 3);
    }

    public function markCompletedVerified(RefundRequest $refund, string $providerReference, ?int $actorId = null): RefundRequest
    {
        $providerReference = trim($providerReference);
        if ($providerReference === '') {
            throw new CommerceException(CommerceErrorCode::RefundUnavailable, 'مرجع تأییدشده Provider برای تکمیل بازپرداخت لازم است.', 422);
        }

        return DB::transaction(function () use ($refund, $providerReference, $actorId): RefundRequest {
            $locked = RefundRequest::query()->whereKey($refund->getKey())->lockForUpdate()->with('order')->firstOrFail();
            if ($locked->status === RefundStatus::Completed) {
                return $locked;
            }
            if ($locked->status !== RefundStatus::Pending || ! config('lbb.payment.refunds_enabled', false)) {
                throw new CommerceException(CommerceErrorCode::RefundUnavailable, 'بازپرداخت Provider-ready نیست و تکمیل جعلی مجاز نیست.', 503);
            }

            $locked->forceFill([
                'status' => RefundStatus::Completed,
                'provider_reference' => $providerReference,
                'completed_at' => now(),
                'failure_message' => null,
            ])->save();

            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->firstOrFail();
            $completed = (int) RefundRequest::query()
                ->where('order_id', $order->getKey())
                ->where('status', RefundStatus::Completed->value)
                ->sum('amount_toman');
            $order->forceFill([
                'payment_status' => $completed >= (int) $order->grand_total_toman
                    ? PaymentStatus::Refunded
                    : PaymentStatus::PartiallyRefunded,
            ])->save();
            $this->audit->record('refund.completed', 'refund_request', $locked->public_id, $order, 'provider', $actorId, [
                'amountToman' => $locked->amount_toman,
            ]);

            return $locked->fresh('order');
        }, 3);
    }

    public function markFailed(RefundRequest $refund, string $message, ?int $actorId = null): RefundRequest
    {
        return DB::transaction(function () use ($refund, $message, $actorId): RefundRequest {
            $locked = RefundRequest::query()->whereKey($refund->getKey())->lockForUpdate()->with('order')->firstOrFail();
            if (! in_array($locked->status, [RefundStatus::Requested, RefundStatus::Pending], true)) {
                throw new CommerceException(CommerceErrorCode::InvalidTransition, 'بازپرداخت در وضعیت قابل شکست نیست.', 409);
            }
            $locked->forceFill([
                'status' => RefundStatus::Failed,
                'failure_message' => mb_substr(trim($message), 0, 1000),
                'failed_at' => now(),
            ])->save();
            $this->audit->record('refund.failed', 'refund_request', $locked->public_id, $locked->order, 'provider', $actorId);

            return $locked;
        }, 3);
    }
}
