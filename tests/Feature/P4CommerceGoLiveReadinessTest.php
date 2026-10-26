<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Services\Store\DeliveryConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class P4CommerceGoLiveReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_p4_shipping_schema_and_public_options_match_the_four_official_methods(): void
    {
        $this->assertTrue(Schema::hasColumns('delivery_zones', [
            'immediate_courier_enabled',
            'immediate_courier_fee_toman',
            'tipax_enabled',
            'tipax_fee_toman',
            'decapost_enabled',
            'decapost_fee_toman',
            'express_post_enabled',
            'express_post_fee_toman',
        ]));

        $official = collect(DeliveryMethod::cases())
            ->filter(static fn (DeliveryMethod $method): bool => $method->isOfficialP4Method())
            ->map(static fn (DeliveryMethod $method): string => $method->value)
            ->values()
            ->all();

        $this->assertSame([
            'immediate_courier',
            'tipax',
            'decapost',
            'express_post',
        ], $official);

        $options = app(DeliveryConfigurationService::class)->options('تهران', 'تهران', 1_000_000);

        $this->assertSame($official, array_column($options, 'method'));
        $this->assertNotContains('standard', array_column($options, 'method'));
        $this->assertNotContains('pickup', array_column($options, 'method'));
    }

    public function test_p4_defaults_are_fail_closed_and_reservation_truth_is_thirty_minutes(): void
    {
        $this->assertSame(30, config('lbb.checkout.reservation_minutes'));
        $this->assertFalse(config('lbb.checkout.enabled'));
        $this->assertFalse(config('lbb.payment.enabled'));
        $this->assertSame('disabled', config('lbb.payment.provider'));

        foreach (['immediate_courier', 'tipax', 'decapost', 'express_post'] as $method) {
            $this->assertFalse(config("lbb.checkout.delivery_methods.{$method}.enabled"));
        }

        $this->assertTrue(config('lbb.launch.frontend_integrated'));
        $this->assertFalse(config('lbb.launch.production_deployed'));
        $this->assertSame('preactivation-candidate', config('lbb.contracts.commerce_go_live.status'));
    }
}
