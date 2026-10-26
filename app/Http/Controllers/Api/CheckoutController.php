<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\IdempotencyConflict;
use App\Exceptions\InventoryUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\Orders\CheckoutService;
use App\Services\Payments\PaymentProviderManager;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use JsonException;

class CheckoutController extends Controller
{
    public function store(
        CheckoutRequest $request,
        CheckoutService $checkout,
        PaymentProviderManager $payments,
    ): JsonResponse {
        if (! config('lbb.checkout.enabled', false)) {
            return ApiResponse::error('ثبت سفارش در حال حاضر فعال نیست.', 503);
        }

        $idempotencyKey = trim((string) $request->header('Idempotency-Key'));
        if (! preg_match('/^[A-Za-z0-9:_-]{16,120}$/', $idempotencyKey)) {
            return ApiResponse::error(
                'کلید Idempotency معتبر نیست.',
                422,
                ['idempotencyKey' => ['یک کلید یکتا با طول ۱۶ تا ۱۲۰ کاراکتر ارسال کنید.']],
            );
        }

        try {
            $result = $checkout->create(
                $request->user('customer'),
                $request->validated(),
                $idempotencyKey,
            );
        } catch (IdempotencyConflict $exception) {
            return ApiResponse::error($exception->getMessage(), 409);
        } catch (InventoryUnavailable $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                422,
                [
                    'items' => [[
                        'variantId' => $exception->variantId,
                        'variantName' => $exception->variantName,
                        'requested' => $exception->requested,
                        'available' => $exception->available,
                    ]],
                ],
            );
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error(
                'اطلاعات گیرنده معتبر نیست.',
                422,
                ['customer.mobile' => [$exception->getMessage()]],
            );
        } catch (JsonException) {
            return ApiResponse::error('امکان پردازش درخواست وجود ندارد.', 422);
        }

        $status = $result['replayed'] ? 200 : 201;
        $paymentReady = $payments->ready();

        return ApiResponse::success([
            'order' => (new OrderResource($result['order']))->resolve($request),
            'payment' => [
                'available' => $paymentReady,
                'state' => $paymentReady ? 'ready' : 'disabled',
                'initiationEndpoint' => $paymentReady
                    ? "/api/orders/{$result['order']->public_id}/payments"
                    : null,
            ],
        ], $result['replayed'] ? 'سفارش قبلی بازیابی شد.' : 'سفارش ثبت و موجودی موقتاً رزرو شد.', $status, [
            'replayed' => $result['replayed'],
        ]);
    }
}
