<?php

namespace App\Providers;

use App\Support\IranianMobile;
use App\Support\PublicCatalogRateLimit;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::before(static fn ($user, string $ability): ?bool => method_exists($user, 'hasRole') && $user->hasRole('super_admin') ? true : null);
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('public-catalog', static fn (Request $request): array => PublicCatalogRateLimit::limits($request));
        RateLimiter::for('public-search', static fn (Request $request): array => [Limit::perMinute(60)->by('public-search-ip:'.($request->ip() ?? 'unknown'))]);
        RateLimiter::for('commerce-cart', static fn (Request $request): array => [
            Limit::perMinute(60)->by('commerce-cart-customer:'.($request->user('customer')?->getKey() ?? $request->ip() ?? 'unknown')),
        ]);
        RateLimiter::for('commerce-checkout', static fn (Request $request): array => [
            Limit::perMinute(15)->by('commerce-checkout-customer:'.($request->user('customer')?->getKey() ?? $request->ip() ?? 'unknown')),
            Limit::perMinute(40)->by('commerce-checkout-ip:'.($request->ip() ?? 'unknown')),
        ]);
        RateLimiter::for('commerce-payment', static fn (Request $request): array => [
            Limit::perMinute(20)->by('commerce-payment-customer:'.($request->user('customer')?->getKey() ?? $request->ip() ?? 'unknown')),
        ]);
        RateLimiter::for('commerce-order', static fn (Request $request): array => [
            Limit::perMinute(90)->by('commerce-order-customer:'.($request->user('customer')?->getKey() ?? $request->ip() ?? 'unknown')),
        ]);
        RateLimiter::for('commerce-return', static fn (Request $request): array => [
            Limit::perMinute(10)->by('commerce-return-customer:'.($request->user('customer')?->getKey() ?? $request->ip() ?? 'unknown')),
        ]);

        RateLimiter::for('otp-request', function (Request $request): array {
            try {
                $mobileKey = IranianMobile::hash((string) $request->input('mobile'));
            } catch (Throwable) {
                $mobileKey = hash('sha256', (string) $request->input('mobile'));
            }

            return [Limit::perMinute(5)->by('otp-request-ip:'.($request->ip() ?? 'unknown')), Limit::perMinute(2)->by('otp-request-mobile:'.$mobileKey)];
        });
        RateLimiter::for('otp-verify', static fn (Request $request): array => [
            Limit::perMinute(15)->by('otp-verify-ip:'.($request->ip() ?? 'unknown')),
            Limit::perMinute(8)->by('otp-verify-challenge:'.(string) $request->input('challengeId')),
        ]);
    }
}
