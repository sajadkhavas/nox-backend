<?php

namespace App\Exceptions;

use RuntimeException;

class OtpCooldown extends RuntimeException
{
    public function __construct(public readonly int $retryAfter)
    {
        parent::__construct('برای دریافت کد جدید کمی صبر کنید.');
    }
}
