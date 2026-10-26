<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class PaymentProviderException extends RuntimeException
{
    public function __construct(
        string $message = 'ارتباط با درگاه پرداخت ناموفق بود.',
        public readonly ?string $providerCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
