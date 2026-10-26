<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Services\Store\DeliveryConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployerShippingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('lbb.checkout.delivery_methods.immediate_courier', [
            'enabled' => true,
            'fee_toman' => 120000,
        ]);
        config()->set('lbb.checkout.delivery_methods.tipax', [
            'enabled' => true,
            'fee_toman' => 85000,
        ]);
        config()->set('lbb.checkout.delivery_methods.decapost', [
            'enabled' => true,
            'fee_toman' => 75000,
        ]);
        config()->set('lbb.checkout.delivery_methods.express_post', [
            'enabled' => true,
            'fee_toman' => 65000,
        ]);
        config()->set('lbb.checkout.delivery_methods.standard', [
            'enabled' => true,
            'fee_toman' => 30000,
        ]);
    }

    public function test_public_options_retain_p4_contract_but_offer_only_employer_approved_methods(): void
    {
        $methods = collect(app(DeliveryConfigurationService::class)->options('تهران', 'تهران', 900000));

        $this->assertSame(
            ['immediate_courier', 'tipax', 'decapost', 'express_post'],
            $methods->pluck('method')->all(),
        );

        $approved = $methods->where('policyEligible', true)->values();
        $this->assertSame(
            ['immediate_courier', 'tipax', 'decapost'],
            $approved->pluck('method')->all(),
        );
        $this->assertTrue($approved->every(fn (array $method): bool => $method['paymentMode'] === 'freight_collect'));
        $this->assertTrue($approved->every(fn (array $method): bool => $method['feeToman'] === 0));
        $this->assertTrue($approved->every(fn (array $method): bool => $method['isFree'] === false));
        $this->assertTrue($approved->every(fn (array $method): bool => str_contains((string) $method['feeNotice'], 'پس‌کرایه')));

        $immediate = $methods->firstWhere('method', 'immediate_courier');
        $this->assertTrue($immediate['enabled']);
        $this->assertSame('اسنپ / اسنپ‌باکس', $immediate['carrier']['label']);
        $this->assertSame('تهران و کرج', $immediate['coverage']['label']);
        $this->assertSame('فوری', $immediate['eta']['label']);

        $tipax = $methods->firstWhere('method', 'tipax');
        $this->assertSame('تمام نقاط ایران', $tipax['coverage']['label']);
        $this->assertSame(['label' => '۳ تا ۷ روز', 'minDays' => 3, 'maxDays' => 7], $tipax['eta']);

        $express = $methods->firstWhere('method', 'express_post');
        $this->assertFalse($express['policyEligible']);
        $this->assertFalse($express['enabled']);
        $this->assertSame('unavailable', $express['paymentMode']);
    }

    public function test_immediate_delivery_is_disabled_outside_tehran_and_karaj(): void
    {
        $methods = collect(app(DeliveryConfigurationService::class)->options('فارس', 'شیراز', 900000));

        $this->assertFalse($methods->firstWhere('method', 'immediate_courier')['enabled']);
        $this->assertTrue($methods->firstWhere('method', 'tipax')['enabled']);
        $this->assertTrue($methods->firstWhere('method', 'decapost')['enabled']);
    }

    public function test_immediate_delivery_accepts_persian_and_english_tehran_karaj_city_names(): void
    {
        $service = app(DeliveryConfigurationService::class);

        foreach (['تهران', 'کرج', 'Tehran', 'Karaj', 'كرج'] as $city) {
            $methods = collect($service->options(null, $city, 900000));
            $this->assertTrue(
                $methods->firstWhere('method', 'immediate_courier')['enabled'],
                "Immediate delivery should be enabled for {$city}",
            );
        }
    }

    public function test_quote_never_charges_approved_shipping_online(): void
    {
        $quote = app(DeliveryConfigurationService::class)->quote(
            DeliveryMethod::Tipax,
            'فارس',
            'شیراز',
            900000,
        );

        $this->assertSame(0, $quote['fee_toman']);
    }

    public function test_quote_rejects_immediate_delivery_outside_tehran_and_karaj(): void
    {
        $this->expectException(ValidationException::class);

        app(DeliveryConfigurationService::class)->quote(
            DeliveryMethod::ImmediateCourier,
            'فارس',
            'شیراز',
            900000,
        );
    }

    public function test_legacy_standard_quote_semantics_remain_available_for_frozen_internal_contracts(): void
    {
        $quote = app(DeliveryConfigurationService::class)->quote(
            DeliveryMethod::Standard,
            'تهران',
            'تهران',
            900000,
        );

        $this->assertSame(30000, $quote['fee_toman']);
    }
}
