<?php

namespace App\Exceptions;

use RuntimeException;

class IdempotencyConflict extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('این کلید Idempotency قبلاً برای درخواست دیگری استفاده شده است.');
    }
}
