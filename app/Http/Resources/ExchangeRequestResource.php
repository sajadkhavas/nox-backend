<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id, 'orderId' => $this->order?->public_id, 'orderItemId' => $this->orderItem?->public_id,
            'sourceVariantId' => $this->sourceVariant?->public_id, 'destinationVariantId' => $this->destinationVariant?->public_id,
            'quantity' => $this->quantity, 'status' => $this->status->value, 'reason' => $this->reason,
            'reservationExpiresAt' => $this->destinationReservation?->expires_at?->toIso8601String(),
            'requestedAt' => $this->requested_at?->toIso8601String(), 'approvedAt' => $this->approved_at?->toIso8601String(),
            'completedAt' => $this->completed_at?->toIso8601String(), 'rejectedAt' => $this->rejected_at?->toIso8601String(),
        ];
    }
}
