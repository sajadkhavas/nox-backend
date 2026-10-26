<?php

namespace App\Http\Controllers\Api;

use App\Enums\CommerceErrorCode;
use App\Exceptions\IdempotencyConflict;
use App\Exceptions\PaymentProviderException;
use App\Exceptions\PaymentUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyPaymentRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentAttemptResource;
use App\Models\Order;
use App\Services\Payments\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;

class PaymentController extends Controller
{
    public function store(Request $request, string $orderId, PaymentService $payments): JsonResponse
    {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key'));
        if (! preg_match('/^[A-Za-z0-9:_-]{16,120}$/', $idempotencyKey)) {
            return ApiResponse::error('کلید Idempotency معتبر نیست.', 422, ['idempotencyKey' => ['یک کلید یکتا با طول ۱۶ تا ۱۲۰ کاراکتر ارسال کنید.']]);
        }
        $order = Order::query()->ownedBy($request->user('customer'))->where('public_id', $orderId)->firstOrFail();
        try {
            $result = $payments->initiate($request->user('customer'), $order, $idempotencyKey);
        } catch (IdempotencyConflict $exception) {
            return ApiResponse::error($exception->getMessage(), 409, code: CommerceErrorCode::DuplicateRequest->value);
        } catch (PaymentUnavailable $exception) {
            return ApiResponse::error($exception->getMessage(), 503, code: CommerceErrorCode::PaymentUnavailable->value);
        } catch (PaymentProviderException $exception) {
            return ApiResponse::error($exception->getMessage(), 502, ['provider' => ['code' => $exception->providerCode]], code: CommerceErrorCode::PaymentUnavailable->value);
        } catch (JsonException) {
            return ApiResponse::error('امکان ایجاد درخواست پرداخت وجود ندارد.', 422);
        }

        return ApiResponse::success([
            'order' => (new OrderResource($result['order']))->resolve($request),
            'payment' => (new PaymentAttemptResource($result['attempt']))->resolve($request),
        ], $result['replayed'] ? 'تلاش پرداخت فعال بازیابی شد.' : 'تلاش پرداخت ایجاد شد.', $result['replayed'] ? 200 : 201, ['replayed' => $result['replayed']]);
    }

    public function verify(VerifyPaymentRequest $request, PaymentService $payments): JsonResponse
    {
        try {
            $result = $payments->verify($request->user('customer'), $request->string('authority')->toString(), $request->string('status')->toString());
        } catch (PaymentUnavailable $exception) {
            return ApiResponse::error($exception->getMessage(), 503, code: CommerceErrorCode::PaymentUnavailable->value);
        } catch (PaymentProviderException $exception) {
            return ApiResponse::error($exception->getMessage(), 502, ['provider' => ['code' => $exception->providerCode]], code: CommerceErrorCode::PaymentUnavailable->value);
        }
        $verified = $result['attempt']->isVerified();

        return ApiResponse::success([
            'verified' => $verified, 'order' => (new OrderResource($result['order']))->resolve($request),
            'payment' => (new PaymentAttemptResource($result['attempt']))->resolve($request),
        ], $verified ? 'پرداخت با موفقیت تأیید شد.' : 'پرداخت تأیید نشد.', 200, ['replayed' => $result['replayed']]);
    }
}
