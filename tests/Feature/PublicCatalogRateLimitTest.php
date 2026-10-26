<?php

namespace Tests\Feature;

use App\Support\PublicCatalogRateLimit;
use Illuminate\Http\Request;
use Tests\TestCase;

class PublicCatalogRateLimitTest extends TestCase
{
    public function test_public_catalog_requests_remain_on_the_public_ip_bucket(): void
    {
        config()->set('lbb.ssr.rate_limit_token', 'server-only-secret');
        config()->set('lbb.ssr.catalog_rate_limit_per_minute', 600);

        $request = Request::create(
            '/api/v1/storefront/bootstrap',
            'GET',
            server: ['REMOTE_ADDR' => '203.0.113.25'],
        );

        $limit = PublicCatalogRateLimit::limits($request)[0];

        $this->assertSame(120, $limit->maxAttempts);
        $this->assertSame('public-catalog-ip:203.0.113.25', $limit->key);
    }

    public function test_valid_server_only_token_selects_the_bounded_ssr_bucket(): void
    {
        config()->set('lbb.ssr.rate_limit_token', 'server-only-secret');
        config()->set('lbb.ssr.catalog_rate_limit_per_minute', 600);

        $request = Request::create(
            '/api/v1/storefront/bootstrap',
            'GET',
            server: [
                'REMOTE_ADDR' => '203.0.113.25',
                'HTTP_X_LBB_SSR_TOKEN' => 'server-only-secret',
            ],
        );

        $limit = PublicCatalogRateLimit::limits($request)[0];

        $this->assertSame(600, $limit->maxAttempts);
        $this->assertSame('public-catalog-ssr', $limit->key);
    }

    public function test_invalid_or_empty_ssr_credentials_fail_closed_to_the_public_bucket(): void
    {
        config()->set('lbb.ssr.rate_limit_token', 'server-only-secret');

        $invalid = Request::create(
            '/api/v1/storefront/bootstrap',
            'GET',
            server: [
                'REMOTE_ADDR' => '198.51.100.7',
                'HTTP_X_LBB_SSR_TOKEN' => 'wrong-secret',
            ],
        );

        $invalidLimit = PublicCatalogRateLimit::limits($invalid)[0];

        $this->assertSame(120, $invalidLimit->maxAttempts);
        $this->assertSame('public-catalog-ip:198.51.100.7', $invalidLimit->key);

        config()->set('lbb.ssr.rate_limit_token', '');

        $emptyConfiguredToken = Request::create(
            '/api/v1/storefront/bootstrap',
            'GET',
            server: [
                'REMOTE_ADDR' => '198.51.100.8',
                'HTTP_X_LBB_SSR_TOKEN' => '',
            ],
        );

        $emptyLimit = PublicCatalogRateLimit::limits($emptyConfiguredToken)[0];

        $this->assertSame(120, $emptyLimit->maxAttempts);
        $this->assertSame('public-catalog-ip:198.51.100.8', $emptyLimit->key);
    }
}
