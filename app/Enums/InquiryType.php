<?php

namespace App\Enums;

enum InquiryType: string
{
    case Contact = 'contact';
    case Gift = 'gift';
    case Corporate = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'تماس عمومی',
            self::Gift => 'سفارش هدیه',
            self::Corporate => 'سفارش سازمانی',
        };
    }
}
