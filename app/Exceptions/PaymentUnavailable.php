<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentUnavailable extends RuntimeException
{
    public function __construct(string $message = 'پرداخت در حال حاضر فعال نیست.')
    {
        parent::__construct($message);
    }
}
