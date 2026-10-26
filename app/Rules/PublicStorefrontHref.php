<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PublicStorefrontHref implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('مقصد عمومی باید یک مسیر یا URL معتبر باشد.');

            return;
        }

        $href = trim($value);
        if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
            return;
        }

        if (filter_var($href, FILTER_VALIDATE_URL) !== false && parse_url($href, PHP_URL_SCHEME) === 'https') {
            return;
        }

        $fail('فقط مسیر داخلی با / یا URL امن HTTPS مجاز است.');
    }
}
