<?php

namespace App\Support;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

final class PublicCatalogRateLimit
{
    public const SSR_TOKEN_HEADER = 'X-LBB-SSR-Token';

    /**
     * @return array<int, Limit>
     */
    public static function limits(Request $request): array
    {
        $configuredToken = trim((string) config('lbb.ssr.rate_limit_token', ''));
        $providedToken = trim((string) $request->header(self::SSR_TOKEN_HEADER, ''));

        $isTrustedSsr = $configuredToken !== ''
            && $providedToken !== ''
            && hash_equals($configuredToken, $providedToken);

        if ($isTrustedSsr) {
            $maxAttempts = max(1, (int) config('lbb.ssr.catalog_rate_limit_per_minute', 600));

            return [Limit::perMinute($maxAttempts)->by('public-catalog-ssr')];
        }

        return [Limit::perMinute(120)->by('public-catalog-ip:'.($request->ip() ?? 'unknown'))];
    }
}
