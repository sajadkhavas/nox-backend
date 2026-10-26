<?php

namespace App\Enums;

enum OrderStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Dispatched = 'dispatched';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'در انتظار پرداخت',
            self::Paid => 'پرداخت‌شده',
            self::Confirmed => 'تأییدشده',
            self::Preparing => 'در حال آماده‌سازی',
            self::Ready => 'آماده ارسال یا تحویل',
            self::Dispatched => 'ارسال‌شده',
            self::Delivered => 'تحویل‌شده',
            self::Cancelled => 'لغوشده',
            self::Expired => 'منقضی‌شده',
        };
    }
}
