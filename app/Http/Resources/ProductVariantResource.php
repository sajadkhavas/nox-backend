<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'priceToman' => $this->current_price_toman,
            'regularPriceToman' => $this->regular_price_toman,
            'salePriceToman' => $this->hasValidSalePrice() ? $this->sale_price_toman : null,
            'stock' => $this->available_stock_quantity,
            'available' => $this->available,
            'lowStock' => $this->low_stock,
            'isDefault' => (bool) $this->is_default,
        ];
    }
}
