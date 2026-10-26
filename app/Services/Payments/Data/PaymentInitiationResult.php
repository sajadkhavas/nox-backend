<?php

namespace App\Services\Payments\Data;

final readonly class PaymentInitiationResult
{
    public function __construct(
        public string $authority,
        public string $redirectUrl,
        public ?string $gatewayCode = null,
        public array $requestPayload = [],
        public array $responsePayload = [],
    ) {}
}
