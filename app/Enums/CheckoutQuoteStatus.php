<?php

namespace App\Enums;

enum CheckoutQuoteStatus: string
{
    case Active = 'active';
    case Consumed = 'consumed';
    case Expired = 'expired';
}
