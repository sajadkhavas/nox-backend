<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id, 'productId' => $this->product_public_id, 'variantId' => $this->variant_public_id,
            'productName' => $this->product_name, 'variantName' => $this->variant_name, 'productCode' => $this->product_code,
            'sku' => $this->sku, 'color' => $this->color_name, 'size' => $this->size_name,
            'unitPrice' => ['amount' => $this->unit_price_toman, 'currency' => $this->currency ?: 'TOMAN'],
            'unitPriceToman' => $this->unit_price_toman, 'quantity' => $this->quantity,
            'lineTotal' => ['amount' => $this->line_total_toman, 'currency' => $this->currency ?: 'TOMAN'],
            'lineTotalToman' => $this->line_total_toman,
        ];
    }
}
