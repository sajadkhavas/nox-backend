<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BackendContractFreezeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'lbb.otp.provider' => 'testing',
            'lbb.otp.expose_test_code' => true,
            'lbb.otp.retry_after_seconds' => 0,
            'session.driver' => 'array',
        ]);
    }

    public function test_frozen_openapi_covers_auth_public_catalog_delivery_and_commerce_contract(): void
    {
        $document = $this->getJson('/api/system/openapi')
            ->assertOk()
            ->assertHeader('ETag')
            ->json();

        $this->assertSame('3.1.0', $document['openapi']);
        $this->assertSame('2026-09-06-p3-storefront-v1', $document['info']['version']);

        $requiredPaths = [
            '/api/system/openapi',
            '/api/v1/auth/otp/request',
            '/api/v1/auth/otp/verify',
            '/api/v1/auth/me',
            '/api/v1/auth/logout',
            '/api/v1/categories',
            '/api/v1/categories/{slug}',
            '/api/v1/products',
            '/api/v1/products/{slug}',
            '/api/v1/collections',
            '/api/v1/collections/{slug}',
            '/api/v1/drops',
            '/api/v1/drops/{slug}',
            '/api/v1/colors',
            '/api/v1/sizes',
            '/api/v1/catalog/facets',
            '/api/v1/delivery/options',
            '/api/v1/search',
            '/api/v1/cart/validate',
            '/api/v1/checkout/quote',
            '/api/v1/checkout/commit',
            '/api/v1/account/orders',
            '/api/v1/account/orders/{orderId}',
            '/api/v1/account/orders/{orderId}/cancel',
            '/api/v1/orders/{orderId}/payments',
            '/api/v1/payments/verify',
            '/api/v1/returns',
            '/api/v1/returns/{returnId}',
            '/api/v1/orders/{orderId}/returns',
            '/api/v1/exchanges',
            '/api/v1/exchanges/{exchangeId}',
            '/api/v1/orders/{orderId}/exchanges',
            '/api/v1/refunds',
            '/api/v1/refunds/{refundId}',
        ];

        foreach ($requiredPaths as $path) {
            $this->assertArrayHasKey($path, $document['paths'], "Missing frozen OpenAPI path: {$path}");
        }

        $this->assertSame([['cookieAuth' => []]], $document['paths']['/api/v1/auth/me']['get']['security']);
        $this->assertSame([['cookieAuth' => []]], $document['paths']['/api/v1/checkout/commit']['post']['security']);
        $this->assertTrue($this->hasRequiredIdempotencyHeader($document['paths']['/api/v1/checkout/commit']['post']));
        $this->assertTrue($this->hasRequiredIdempotencyHeader($document['paths']['/api/v1/orders/{orderId}/payments']['post']));
        $this->assertSame('integer', $document['components']['schemas']['Money']['properties']['amount']['type']);
        $this->assertSame('TOMAN', $document['components']['schemas']['Money']['properties']['currency']['const']);
        $this->assertFalse($document['x-lbb-freeze']['frontendAuthoritativePriceOrStock']);
        $this->assertSame('fail-closed', $document['x-lbb-freeze']['paymentWithoutProvider']);
        $this->assertFalse($document['x-lbb-freeze']['frontendIntegrated']);
        $this->assertFalse($document['x-lbb-freeze']['productionDeployed']);
    }

    public function test_versioned_delivery_options_expose_server_configuration_without_legacy_route_dependency(): void
    {
        $this->getJson('/api/v1/delivery/options?province=تهران&city=تهران&subtotalToman=1000000')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'zone',
                    'methods' => [
                        '*' => ['method', 'label', 'enabled', 'feeToman'],
                    ],
                ],
                'meta' => ['apiVersion', 'contractVersion'],
            ])
            ->assertJsonPath('meta.contractVersion', '2026-09-06-p3-storefront-v1');
    }

    public function test_versioned_auth_surface_establishes_session_without_using_legacy_routes(): void
    {
        $this->stateful()->getJson('/api/v1/auth/me')->assertUnauthorized();

        $challenge = $this->stateful()->postJson('/api/v1/auth/otp/request', ['mobile' => '09121234567'])
            ->assertAccepted()
            ->assertJsonPath('success', true);

        $challengeId = (string) $challenge->json('data.challengeId');
        $code = (string) $challenge->json('data.debugCode');

        $this->stateful()->postJson('/api/v1/auth/otp/verify', [
            'mobile' => '09121234567',
            'challengeId' => $challengeId,
            'code' => $code,
        ])->assertOk()->assertJsonPath('data.user.mobileVerified', true);

        $this->stateful()->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.mobile', '09121234567');

        $this->stateful()->postJson('/api/v1/auth/logout')->assertOk();
        $this->stateful()->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_backend_contract_stays_frozen_while_launch_metadata_records_p3_frontend_integration(): void
    {
        $this->getJson('/api/system/contracts')
            ->assertOk()
            ->assertJsonPath('data.contractVersion', '2026-09-06-p3-storefront-v1')
            ->assertJsonPath('data.contracts.domain_cleanup.status', 'ready')
            ->assertJsonPath('data.contracts.apparel_domain.status', 'ready')
            ->assertJsonPath('data.contracts.catalog.status', 'public-v1-ready')
            ->assertJsonPath('data.contracts.authentication.status', 'public-v1-ready')
            ->assertJsonPath('data.contracts.authentication.source', 'f14-be-f1')
            ->assertJsonPath('data.contracts.orders.status', 'commerce-operations-ready')
            ->assertJsonPath('data.contracts.backend_freeze.status', 'ready')
            ->assertJsonPath('data.contracts.backend_freeze.source', 'f14-be-f1')
            ->assertJsonPath('data.launch.backend_complete', true)
            ->assertJsonPath('data.launch.frontend_integrated', true)
            ->assertJsonPath('data.launch.production_deployed', false);

        $this->assertSame(0, Artisan::call('backend:readiness', ['--json' => true]));
    }

    private function hasRequiredIdempotencyHeader(array $operation): bool
    {
        foreach ($operation['parameters'] ?? [] as $parameter) {
            if (($parameter['name'] ?? null) === 'Idempotency-Key'
                && ($parameter['in'] ?? null) === 'header'
                && ($parameter['required'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    private function stateful(): static
    {
        return $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
            'User-Agent' => 'LBB-F14D-Contract-Test/1.0',
        ]);
    }
}
