<?php

namespace Tests\Unit;

use Tests\TestCase;

class BackendFoundationTest extends TestCase
{
    public function test_frontend_origins_are_configured_as_an_array(): void
    {
        $origins = config('lbb.frontend_origins');

        $this->assertIsArray($origins);
        $this->assertNotEmpty($origins);
        $this->assertSame($origins, config('cors.allowed_origins'));
        $this->assertTrue((bool) config('cors.supports_credentials'));
    }

    public function test_p4_preserves_backend_acceptance_and_external_boundaries_after_p3_frontend_integration(): void
    {
        $contracts = config('lbb.contracts');

        $this->assertSame('2026-09-06-p3-storefront-v1', config('lbb.api.contract_version'));
        $this->assertSame('implemented', $contracts['system']['status']);
        $this->assertSame('ready', $contracts['domain_cleanup']['status']);
        $this->assertSame('public-v1-ready', $contracts['catalog']['status']);
        $this->assertSame('ready', $contracts['apparel_domain']['status']);
        $this->assertSame('public-v1-ready', $contracts['authentication']['status']);
        $this->assertSame('f14-be-f1', $contracts['authentication']['source']);
        $this->assertSame('commerce-operations-ready', $contracts['orders']['status']);
        $this->assertSame('provider-ready-fail-closed', $contracts['payments']['status']);
        $this->assertSame('commerce-operations-ready', $contracts['store_operations']['status']);
        $this->assertSame('public-v1-ready', $contracts['storefront_content']['status']);
        $this->assertSame('p3-storefront-v1', $contracts['storefront_content']['source']);
        $this->assertSame('preactivation-candidate', $contracts['commerce_go_live']['status']);
        $this->assertSame(30, $contracts['commerce_go_live']['reservation_minutes']);
        $this->assertSame('ready', $contracts['backend_freeze']['status']);
        $this->assertSame('f14-be-f1', $contracts['backend_freeze']['source']);
        $this->assertSame('2026-09-06-p3-storefront-v1', $contracts['backend_freeze']['contract_version']);
        $this->assertTrue((bool) config('lbb.launch.backend_complete'));
        $this->assertTrue((bool) config('lbb.launch.frontend_integrated'));
        $this->assertFalse((bool) config('lbb.launch.production_deployed'));
    }
}
