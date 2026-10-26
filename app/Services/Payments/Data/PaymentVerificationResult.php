<?php

namespace App\Services\Payments\Data;

final readonly class PaymentVerificationResult
{
    public function __construct(
        public bool $verified,
        public string $state,
        public ?string $referenceId = null,
        public ?string $gatewayCode = null,
        public ?string $failureCode = null,
        public ?string $message = null,
        public array $payload = [],
    ) {}

    public static function verified(
        ?string $referenceId,
        ?string $gatewayCode,
        array $payload = [],
    ): self {
        return new self(true, 'verified', $referenceId, $gatewayCode, payload: $payload);
    }

    public static function failed(
        string $state,
        ?string $failureCode,
        ?string $message,
        ?string $gatewayCode = null,
        array $payload = [],
    ): self {
        return new self(false, $state, gatewayCode: $gatewayCode, failureCode: $failureCode, message: $message, payload: $payload);
    }
}
