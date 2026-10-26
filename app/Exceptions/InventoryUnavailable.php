<?php

namespace App\Exceptions;

use RuntimeException;

class InventoryUnavailable extends RuntimeException
{
    public function __construct(
        public readonly string $variantId,
        public readonly string $variantName,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct('موجودی یکی از اقلام سبد کافی نیست.');
    }
}
