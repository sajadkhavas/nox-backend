<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Sms = 'sms';
    case WebPush = 'web_push';

    public function label(): string
    {
        return match ($this) {
            self::Sms => 'پیامک',
            self::WebPush => 'اعلان وب',
        };
    }
}
