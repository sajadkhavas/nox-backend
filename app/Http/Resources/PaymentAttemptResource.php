<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'provider' => $this->provider,
            'attemptNumber' => $this->attempt_number,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'amountToman' => $this->amount_toman,
            'currency' => $this->currency,
            'authority' => $this->authority,
            'referenceId' => $this->reference_id,
            'gatewayCode' => $this->gateway_code,
            'redirectUrl' => $this->redirect_url,
            'failure' => $this->failure_code || $this->failure_message ? [
                'code' => $this->failure_code,
                'message' => $this->failure_message,
            ] : null,
            'expiresAt' => $this->expires_at?->toIso8601String(),
            'verifiedAt' => $this->verified_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
