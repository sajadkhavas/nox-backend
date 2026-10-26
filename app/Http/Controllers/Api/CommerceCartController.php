<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommerceCartRequest;
use App\Services\Commerce\CartValidationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CommerceCartController extends Controller
{
    public function validateCart(CommerceCartRequest $request, CartValidationService $cart): JsonResponse
    {
        $snapshot = $cart->validate($request->user('customer'), $request->validated());

        return ApiResponse::success([
            'items' => collect($snapshot['items'])->map(fn (array $item): array => [
                ...$item,
                'unitPrice' => ['amount' => $item['unitPriceToman'], 'currency' => $snapshot['currency']],
                'lineTotal' => ['amount' => $item['lineTotalToman'], 'currency' => $snapshot['currency']],
            ])->all(),
            'delivery' => ['method' => $snapshot['deliveryMethod'], 'zoneId' => $snapshot['deliveryZonePublicId']],
            'totals' => [
                'subtotal' => ['amount' => $snapshot['subtotalToman'], 'currency' => $snapshot['currency']],
                'deliveryFee' => ['amount' => $snapshot['deliveryFeeToman'], 'currency' => $snapshot['currency']],
                'packagingFee' => ['amount' => $snapshot['packagingFeeToman'], 'currency' => $snapshot['currency']],
                'discount' => ['amount' => $snapshot['discountTotalToman'], 'currency' => $snapshot['currency']],
                'grandTotal' => ['amount' => $snapshot['grandTotalToman'], 'currency' => $snapshot['currency']],
            ],
            'currency' => $snapshot['currency'],
        ]);
    }
}
