<?php

namespace App\Support;

final class CustomerDetails
{
    public const POSTAL_CODE_ERROR = 'کد پستی واقعی ۱۰ رقمی را وارد کنید.';

    public static function asciiDigits(string $value): string
    {
        return strtr($value, array_combine(
            preg_split('//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY),
            str_split('01234567890123456789'),
        ));
    }

    public static function postalCode(string $value): string
    {
        return preg_replace('/[\s-]+/u', '', self::asciiDigits($value)) ?? '';
    }

    public static function validPostalCode(string $value): bool
    {
        $code = self::postalCode($value);

        return preg_match('/\A[0-9]{10}\z/', $code) === 1
            && preg_match('/\A([0-9])\1{9}\z/', $code) !== 1
            && ! in_array($code, ['1234567890', '0123456789', '0987654321', '9876543210'], true);
    }

    public static function validFullName(string $value): bool
    {
        return preg_match('/\p{L}[^\s]*\s+.*\p{L}/u', trim($value)) === 1;
    }
}
