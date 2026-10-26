<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id, 'orderId' => $this->order?->public_id, 'status' => $this->status->value,
            'resolution' => $this->resolution?->value, 'reason' => $this->reason,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'orderItemId' => $item->orderItem?->public_id, 'quantity' => $item->quantity,
                'refundValue' => ['amount' => $item->refund_value_toman, 'currency' => 'TOMAN'],
            ])->values()->all(), []),
            'requestedAt' => $this->requested_at?->toIso8601String(), 'approvedAt' => $this->approved_at?->toIso8601String(),
            'rejectedAt' => $this->rejected_at?->toIso8601String(), 'receivedAt' => $this->received_at?->toIso8601String(),
            'resolvedAt' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
