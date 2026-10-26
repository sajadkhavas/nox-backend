<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\ApiResponse;
use App\Support\IranianMobile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class PublicOrderTrackingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orderNumber' => ['required', 'string', 'max:64'],
            'mobile' => ['required', 'string', 'max:32'],
        ]);

        try {
            $mobile = IranianMobile::normalize((string) $validated['mobile']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'mobile' => ['شماره موبایل معتبر نیست.'],
            ]);
        }

        $order = Order::query()
            ->with('shipment')
            ->where('order_number', trim((string) $validated['orderNumber']))
            ->where('customer_mobile', $mobile)
            ->first();

        if ($order === null) {
            return ApiResponse::error('سفارشی با این اطلاعات پیدا نشد.', 404);
        }

        return ApiResponse::success([
            'orderNumber' => $order->order_number,
            'status' => $order->status->value,
            'paymentStatus' => $order->payment_status->value,
            'deliveryMethod' => $order->delivery_method?->value,
            'placedAt' => $order->placed_at?->toIso8601String(),
            'confirmedAt' => $order->confirmed_at?->toIso8601String(),
            'preparingAt' => $order->preparing_at?->toIso8601String(),
            'readyAt' => $order->ready_at?->toIso8601String(),
            'dispatchedAt' => $order->dispatched_at?->toIso8601String(),
            'deliveredAt' => $order->delivered_at?->toIso8601String(),
            'trackingCode' => $order->tracking_code,
            'shipment' => $order->shipment === null ? null : [
                'status' => $order->shipment->status->value,
                'carrier' => $order->shipment->carrier,
                'trackingReference' => $order->shipment->tracking_reference,
                'readyAt' => $order->shipment->ready_at?->toIso8601String(),
                'shippedAt' => $order->shipment->shipped_at?->toIso8601String(),
                'deliveredAt' => $order->shipment->delivered_at?->toIso8601String(),
            ],
        ]);
    }
}
