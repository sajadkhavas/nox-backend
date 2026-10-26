<?php

namespace App\Http\Controllers\Api;

use App\Enums\CommerceErrorCode;
use App\Exceptions\IdempotencyConflict;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReturnRequest;
use App\Http\Resources\ReturnRequestResource;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\Commerce\ReturnService;
use App\Support\ApiResponse;
use App\Support\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommerceReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $returns = ReturnRequest::query()->ownedBy($request->user('customer'))->with(['order', 'items.orderItem'])
            ->latest('requested_at')->paginate(min(30, max(1, (int) $request->input('perPage', 10))));

        return ApiResponse::success(ReturnRequestResource::collection($returns->getCollection())->resolve($request), meta: ['pagination' => Pagination::meta($returns)]);
    }

    public function store(CreateReturnRequest $request, string $orderId, ReturnService $returns): JsonResponse
    {
        $order = Order::query()->ownedBy($request->user('customer'))->where('public_id', $orderId)->firstOrFail();
        $key = $this->key($request);
        try {
            $result = $returns->request($request->user('customer'), $order, $request->validated(), $key);
        } catch (IdempotencyConflict $e) {
            return ApiResponse::error($e->getMessage(), 409, code: CommerceErrorCode::DuplicateRequest->value);
        }

        return ApiResponse::success(['return' => (new ReturnRequestResource($result['return']))->resolve($request)], null, $result['replayed'] ? 200 : 201, ['replayed' => $result['replayed']]);
    }

    public function show(Request $request, string $returnId): JsonResponse
    {
        $return = ReturnRequest::query()->ownedBy($request->user('customer'))->where('public_id', $returnId)->with(['order', 'items.orderItem'])->firstOrFail();

        return ApiResponse::success(['return' => (new ReturnRequestResource($return))->resolve($request)]);
    }

    private function key(Request $request): string
    {
        $key = trim((string) $request->header('Idempotency-Key'));
        if (! preg_match('/^[A-Za-z0-9:_-]{16,120}$/', $key)) {
            throw ValidationException::withMessages(['idempotencyKey' => ['کلید Idempotency معتبر الزامی است.']]);
        }

        return $key;
    }
}
