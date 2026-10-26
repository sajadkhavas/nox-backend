<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalAdminDrivenStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_exposes_final_admin_driven_defaults_without_a_schema_migration(): void
    {
        $response = $this->getJson('/api/v1/storefront/bootstrap')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.contractVersion', '2026-09-06-p3-storefront-v1')
            ->assertJsonPath('data.runtime.checkoutEnabled', false)
            ->assertJsonPath('data.runtime.payment.enabled', false)
            ->assertJsonPath('data.runtime.payment.provider', 'disabled');

        $settings = $response->json('data.settings');
        $this->assertIsArray($settings);

        $this->assertIsArray($settings['home']['home.ticker'] ?? null);
        $this->assertIsArray($settings['home']['home.trust'] ?? null);
        $this->assertIsArray($settings['home']['home.section_copy'] ?? null);
        $this->assertIsArray($settings['home']['home.decision_support'] ?? null);
        $this->assertIsArray($settings['home']['home.local_store'] ?? null);
        $this->assertIsArray($settings['home']['home.featured_story'] ?? null);
        $this->assertIsArray($settings['policy']['policy.returns'] ?? null);
        $this->assertIsArray($settings['trust']['trust.enamad'] ?? null);

        $contact = $settings['contact']['contact.public'] ?? null;
        $this->assertIsArray($contact);
        $this->assertArrayHasKey('email', $contact);
        $this->assertArrayHasKey('addressLine', $contact);
        $this->assertArrayHasKey('mapUrl', $contact);
        $this->assertArrayHasKey('openingHours', $contact);
    }

    public function test_existing_public_admin_setting_overrides_server_default(): void
    {
        StoreSetting::query()->updateOrCreate(
            ['key' => 'home.ticker'],
            [
                'group' => 'home',
                'label' => 'Ticker override',
                'type' => 'json',
                'value' => json_encode(['ADMIN OVERRIDE'], JSON_THROW_ON_ERROR),
                'is_public' => true,
            ],
        );

        $response = $this->getJson('/api/v1/storefront/bootstrap')->assertOk();
        $settings = $response->json('data.settings');

        $this->assertSame(['ADMIN OVERRIDE'], $settings['home']['home.ticker'] ?? null);
    }

    public function test_private_settings_never_leak_through_final_bootstrap(): void
    {
        StoreSetting::query()->create([
            'group' => 'home',
            'key' => 'home.private_secret',
            'label' => 'Secret',
            'type' => 'string',
            'value' => 'DO-NOT-LEAK',
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/v1/storefront/bootstrap')->assertOk();
        $this->assertStringNotContainsString('DO-NOT-LEAK', $response->getContent());
        $this->assertStringNotContainsString('home.private_secret', $response->getContent());
    }

    public function test_runtime_commerce_state_is_derived_from_backend_config_not_store_settings(): void
    {
        StoreSetting::query()->updateOrCreate(
            ['key' => 'runtime.fake_payment_enabled'],
            [
                'group' => 'runtime',
                'label' => 'Must not control payment',
                'type' => 'boolean',
                'value' => '1',
                'is_public' => true,
            ],
        );

        config()->set('lbb.checkout.enabled', false);
        config()->set('lbb.payment.enabled', false);
        config()->set('lbb.payment.provider', 'zarinpal');

        $this->getJson('/api/v1/storefront/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.runtime.checkoutEnabled', false)
            ->assertJsonPath('data.runtime.payment.enabled', false)
            ->assertJsonPath('data.runtime.payment.provider', 'disabled');

        config()->set('lbb.checkout.enabled', true);
        config()->set('lbb.payment.enabled', true);
        config()->set('lbb.payment.provider', 'zarinpal');

        $this->getJson('/api/v1/storefront/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.runtime.checkoutEnabled', true)
            ->assertJsonPath('data.runtime.payment.enabled', true)
            ->assertJsonPath('data.runtime.payment.provider', 'zarinpal');
    }

    public function test_stale_non_lbb_site_data_seeder_has_been_removed(): void
    {
        $this->assertFileDoesNotExist(database_path('seeders/SiteDataSeeder.php'));
    }
}
