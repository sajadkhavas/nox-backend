<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id, 'orderId' => $this->order?->public_id, 'status' => $this->status->value,
            'amount' => ['amount' => $this->amount_toman, 'currency' => $this->currency], 'provider' => $this->provider,
            'providerReference' => $this->provider_reference, 'reason' => $this->reason,
            'requestedAt' => $this->requested_at?->toIso8601String(), 'completedAt' => $this->completed_at?->toIso8601String(),
            'failedAt' => $this->failed_at?->toIso8601String(),
        ];
    }
}
