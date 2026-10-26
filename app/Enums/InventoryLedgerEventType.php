<?php

namespace App\Enums;

enum InventoryLedgerEventType: string
{
    case OpeningBalance = 'opening_balance';
    case ReservationCreated = 'reservation_created';
    case ReservationReleased = 'reservation_released';
    case ReservationExpired = 'reservation_expired';
    case Sale = 'sale';
    case Return = 'return';
    case ExchangeIn = 'exchange_in';
    case ExchangeOut = 'exchange_out';
    case Adjustment = 'adjustment';
    case ManualCorrection = 'manual_correction';
}
