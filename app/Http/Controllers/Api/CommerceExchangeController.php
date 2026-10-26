<?php

namespace App\Http\Controllers\Api;

use App\Enums\CommerceErrorCode;
use App\Exceptions\IdempotencyConflict;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateExchangeRequest;
use App\Http\Resources\ExchangeRequestResource;
use App\Models\ExchangeRequest;
use App\Models\Order;
use App\Services\Commerce\ExchangeService;
use App\Support\ApiResponse;
use App\Support\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommerceExchangeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = ExchangeRequest::query()->ownedBy($request->user('customer'))->with(['order', 'orderItem', 'sourceVariant', 'destinationVariant', 'destinationReservation'])
            ->latest('requested_at')->paginate(min(30, max(1, (int) $request->input('perPage', 10))));

        return ApiResponse::success(ExchangeRequestResource::collection($items->getCollection())->resolve($request), meta: ['pagination' => Pagination::meta($items)]);
    }

    public function store(CreateExchangeRequest $request, string $orderId, ExchangeService $service): JsonResponse
    {
        $order = Order::query()->ownedBy($request->user('customer'))->where('public_id', $orderId)->firstOrFail();
        $key = trim((string) $request->header('Idempotency-Key'));
        if (! preg_match('/^[A-Za-z0-9:_-]{16,120}$/', $key)) {
            throw ValidationException::withMessages(['idempotencyKey' => ['کلید Idempotency معتبر الزامی است.']]);
        }
        try {
            $result = $service->request($request->user('customer'), $order, $request->validated(), $key);
        } catch (IdempotencyConflict $e) {
            return ApiResponse::error($e->getMessage(), 409, code: CommerceErrorCode::DuplicateRequest->value);
        }

        return ApiResponse::success(['exchange' => (new ExchangeRequestResource($result['exchange']))->resolve($request)], null, $result['replayed'] ? 200 : 201, ['replayed' => $result['replayed']]);
    }

    public function show(Request $request, string $exchangeId): JsonResponse
    {
        $exchange = ExchangeRequest::query()->ownedBy($request->user('customer'))->where('public_id', $exchangeId)
            ->with(['order', 'orderItem', 'sourceVariant', 'destinationVariant', 'destinationReservation'])->firstOrFail();

        return ApiResponse::success(['exchange' => (new ExchangeRequestResource($exchange))->resolve($request)]);
    }
}
