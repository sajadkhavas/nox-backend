<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در صف',
            self::Processing => 'در حال ارسال',
            self::Sent => 'ارسال‌شده',
            self::Failed => 'ناموفق',
            self::Cancelled => 'لغوشده',
        };
    }
}
