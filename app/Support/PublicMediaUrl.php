<?php

namespace App\Support;

final class PublicMediaUrl
{
    public static function fromPathOrUrl(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'https://')) {
            return $value;
        }

        // Never emit mixed-content HTTP media on the public HTTPS storefront.
        if (str_starts_with($value, 'http://')) {
            return null;
        }

        if (str_starts_with($value, '/storage/')) {
            return rtrim((string) config('app.url'), '/').$value;
        }

        if (str_starts_with($value, 'storage/')) {
            return rtrim((string) config('app.url'), '/').'/'.$value;
        }

        return asset('storage/'.ltrim($value, '/'));
    }
}
