<?php

namespace App\Exceptions;

use App\Enums\CommerceErrorCode;
use RuntimeException;

final class CommerceException extends RuntimeException
{
    public function __construct(
        public readonly CommerceErrorCode $commerceCode,
        string $message,
        public readonly int $status = 422,
        public readonly array $errors = [],
        public readonly array $meta = [],
    ) {
        parent::__construct($message);
    }
}
