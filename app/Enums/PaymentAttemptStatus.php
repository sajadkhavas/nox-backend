<?php

namespace App\Enums;

enum PaymentAttemptStatus: string
{
    case Initiated = 'initiated';
    case Pending = 'pending';
    case Verified = 'verified';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Initiated => 'در حال ایجاد',
            self::Pending => 'در انتظار پرداخت',
            self::Verified => 'تأییدشده',
            self::Failed => 'ناموفق',
            self::Cancelled => 'لغوشده',
            self::Expired => 'منقضی‌شده',
        };
    }

    public function terminal(): bool
    {
        return in_array($this, [
            self::Verified,
            self::Failed,
            self::Cancelled,
            self::Expired,
        ], true);
    }
}
