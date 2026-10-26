<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'mobile' => $this->mobile,
            'fullName' => $this->full_name,
            'email' => $this->email,
            'mobileVerified' => $this->mobile_verified_at !== null,
            'marketingConsent' => (bool) $this->marketing_consent,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
