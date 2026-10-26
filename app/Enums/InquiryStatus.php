<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جدید',
            self::InProgress => 'در حال پیگیری',
            self::Resolved => 'بسته‌شده',
            self::Spam => 'هرزنامه',
        };
    }
}
