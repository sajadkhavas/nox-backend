<?php

namespace App\Support;

use InvalidArgumentException;

final class IranianMobile
{
    private const DIGIT_MAP = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public static function normalize(string $value): string
    {
        $mobile = strtr(trim($value), self::DIGIT_MAP);
        $mobile = preg_replace('/[^0-9+]/', '', $mobile) ?? '';

        if (str_starts_with($mobile, '+98')) {
            $mobile = '0'.substr($mobile, 3);
        } elseif (str_starts_with($mobile, '0098')) {
            $mobile = '0'.substr($mobile, 4);
        } elseif (str_starts_with($mobile, '98') && strlen($mobile) === 12) {
            $mobile = '0'.substr($mobile, 2);
        }

        if (! preg_match('/^09\d{9}$/', $mobile)) {
            throw new InvalidArgumentException('شماره موبایل معتبر نیست.');
        }

        return $mobile;
    }

    public static function hash(string $mobile): string
    {
        return hash_hmac('sha256', self::normalize($mobile), (string) config('app.key'));
    }
}
