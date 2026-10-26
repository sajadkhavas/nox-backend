<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Store\DeliveryConfigurationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function options(Request $request, DeliveryConfigurationService $delivery): JsonResponse
    {
        $validated = $request->validate([
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'subtotalToman' => ['nullable', 'integer', 'min:0'],
        ]);
        $subtotal = (int) ($validated['subtotalToman'] ?? 0);
        $zone = $delivery->resolve($validated['province'] ?? null, $validated['city'] ?? null);

        return ApiResponse::success([
            'zone' => $zone ? [
                'id' => $zone->public_id,
                'name' => $zone->name,
                'minimumOrderToman' => $zone->minimum_order_toman,
                'freeDeliveryThresholdToman' => $zone->free_delivery_threshold_toman,
                'packagingFeeToman' => $zone->packaging_fee_toman,
                'processing' => [
                    'minDays' => $zone->preparation_min_days,
                    'maxDays' => max($zone->preparation_min_days, $zone->preparation_max_days),
                ],
            ] : null,
            'methods' => $delivery->options(
                $validated['province'] ?? null,
                $validated['city'] ?? null,
                $subtotal,
            ),
        ]);
    }
}
