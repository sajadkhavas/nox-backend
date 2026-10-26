<?php

namespace App\Enums;

enum InventoryReservationStatus: string
{
    case Active = 'active';
    case Consumed = 'consumed';
    case Released = 'released';
    case Expired = 'expired';
    case Restocked = 'restocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'فعال',
            self::Consumed => 'مصرف‌شده',
            self::Released => 'آزادشده',
            self::Expired => 'منقضی‌شده',
            self::Restocked => 'به موجودی برگشته',
        };
    }
}
