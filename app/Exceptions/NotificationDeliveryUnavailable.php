<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class NotificationDeliveryUnavailable extends RuntimeException
{
    public function __construct(
        string $message = 'ارسال اعلان در حال حاضر در دسترس نیست.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
