<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefundRequestResource;
use App\Models\RefundRequest;
use App\Support\ApiResponse;
use App\Support\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommerceRefundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $refunds = RefundRequest::query()->where('customer_id', $request->user('customer')->getKey())->with('order')
            ->latest('requested_at')->paginate(min(30, max(1, (int) $request->input('perPage', 10))));

        return ApiResponse::success(RefundRequestResource::collection($refunds->getCollection())->resolve($request), meta: ['pagination' => Pagination::meta($refunds)]);
    }

    public function show(Request $request, string $refundId): JsonResponse
    {
        $refund = RefundRequest::query()->where('customer_id', $request->user('customer')->getKey())->where('public_id', $refundId)->with('order')->firstOrFail();

        return ApiResponse::success(['refund' => (new RefundRequestResource($refund))->resolve($request)]);
    }
}
