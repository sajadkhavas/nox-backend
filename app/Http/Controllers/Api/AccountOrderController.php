<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderLifecycleService;
use App\Support\ApiResponse;
use App\Support\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:'.config('lbb.policies.pagination.account_max', 30)],
        ]);
        $orders = Order::query()->ownedBy($request->user('customer'))
            ->with(['items', 'paymentAttempts', 'deliveryZone', 'shipment', 'returns.items.orderItem', 'exchanges.destinationVariant', 'refunds'])
            ->latest('placed_at')->paginate((int) ($filters['perPage'] ?? config('lbb.policies.pagination.account_default', 10)));

        return ApiResponse::success(OrderResource::collection($orders->getCollection())->resolve($request), meta: ['pagination' => Pagination::meta($orders)]);
    }

    public function show(Request $request, string $orderId): JsonResponse
    {
        $order = Order::query()->ownedBy($request->user('customer'))->where('public_id', $orderId)
            ->with(['items', 'reservations', 'paymentAttempts', 'deliveryZone', 'statusHistory', 'shipment',
                'returns.items.orderItem', 'exchanges.orderItem', 'exchanges.sourceVariant', 'exchanges.destinationVariant',
                'exchanges.destinationReservation', 'refunds'])
            ->firstOrFail();

        return ApiResponse::success(['order' => (new OrderResource($order))->resolve($request)]);
    }

    public function cancel(Request $request, string $orderId, OrderLifecycleService $lifecycle): JsonResponse
    {
        $order = Order::query()->ownedBy($request->user('customer'))->where('public_id', $orderId)->firstOrFail();
        $cancelled = $lifecycle->cancelByCustomer($order, $request->user('customer'));

        return ApiResponse::success(['order' => (new OrderResource($cancelled))->resolve($request)], 'سفارش لغو و رزرو موجودی آزاد شد.');
    }
}
