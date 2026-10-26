<?php

namespace App\Http\Controllers\Api;

use App\Enums\CommerceErrorCode;
use App\Exceptions\IdempotencyConflict;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommerceCartRequest;
use App\Http\Requests\CommerceCommitRequest;
use App\Http\Resources\OrderResource;
use App\Services\Commerce\CheckoutQuoteService;
use App\Services\Commerce\CommerceCheckoutService;
use App\Services\Payments\PaymentProviderManager;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CommerceCheckoutController extends Controller
{
    public function quote(CommerceCartRequest $request, CheckoutQuoteService $quotes): JsonResponse
    {
        if (! config('lbb.checkout.enabled', false)) {
            return ApiResponse::error('Checkout در حال حاضر فعال نیست.', 503, code: CommerceErrorCode::PaymentUnavailable->value);
        }
        $result = $quotes->create($request->user('customer'), $request->validated());
        $quote = $result['quote'];
        $snapshot = $result['snapshot'];

        return ApiResponse::success([
            'quoteId' => $quote->public_id, 'status' => $quote->status->value, 'expiresAt' => $quote->expires_at->toIso8601String(),
            'items' => collect($snapshot['items'])->map(fn (array $item): array => [
                ...$item, 'unitPrice' => ['amount' => $item['unitPriceToman'], 'currency' => $snapshot['currency']],
                'lineTotal' => ['amount' => $item['lineTotalToman'], 'currency' => $snapshot['currency']],
            ])->all(),
            'delivery' => ['method' => $snapshot['deliveryMethod'], 'zoneId' => $snapshot['deliveryZonePublicId']],
            'totals' => [
                'subtotal' => ['amount' => $snapshot['subtotalToman'], 'currency' => $snapshot['currency']],
                'deliveryFee' => ['amount' => $snapshot['deliveryFeeToman'], 'currency' => $snapshot['currency']],
                'packagingFee' => ['amount' => $snapshot['packagingFeeToman'], 'currency' => $snapshot['currency']],
                'discount' => ['amount' => $snapshot['discountTotalToman'], 'currency' => $snapshot['currency']],
                'grandTotal' => ['amount' => $snapshot['grandTotalToman'], 'currency' => $snapshot['currency']],
            ], 'currency' => $snapshot['currency'],
        ], 'Quote معتبر سرور ایجاد شد.', 201);
    }

    public function commit(CommerceCommitRequest $request, CommerceCheckoutService $checkout, PaymentProviderManager $payments): JsonResponse
    {
        $key = $this->idempotencyKey($request);
        try {
            $result = $checkout->commit($request->user('customer'), $request->string('quoteId')->toString(), $key);
        } catch (IdempotencyConflict $e) {
            return ApiResponse::error($e->getMessage(), 409, code: CommerceErrorCode::DuplicateRequest->value);
        }

        return ApiResponse::success([
            'order' => (new OrderResource($result['order']))->resolve($request),
            'payment' => [
                'available' => $payments->ready(), 'state' => $payments->ready() ? 'ready' : 'disabled',
                'initiationEndpoint' => $payments->ready() ? "/api/v1/orders/{$result['order']->public_id}/payments" : null,
            ],
        ], $result['replayed'] ? 'Checkout قبلی بازیابی شد.' : 'Checkout Commit انجام شد.', $result['replayed'] ? 200 : 201, ['replayed' => $result['replayed']]);
    }

    private function idempotencyKey($request): string
    {
        $key = trim((string) $request->header('Idempotency-Key'));
        if (! preg_match('/^[A-Za-z0-9:_-]{16,120}$/', $key)) {
            throw ValidationException::withMessages(['idempotencyKey' => ['کلید یکتا با طول ۱۶ تا ۱۲۰ کاراکتر الزامی است.']]);
        }

        return $key;
    }
}
