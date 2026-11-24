<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NoxServicesCommandTest extends TestCase
{
    public function test_readiness_reports_missing_settings_without_provider_requests(): void
    {
        Http::preventStrayRequests();
        config(['lbb.otp.provider' => 'disabled', 'lbb.payment.enabled' => false]);
        $this->artisan('nox:services')->assertExitCode(1);
        Http::assertNothingSent();
    }

    public function test_readiness_accepts_configured_providers_without_sending_sms_or_payment(): void
    {
        Http::preventStrayRequests();
        config([
            'lbb.otp.provider' => 'kavenegar', 'lbb.otp.kavenegar.api_key' => 'test-placeholder',
            'lbb.otp.kavenegar.template' => 'nox-otp', 'lbb.otp.expose_test_code' => false,
            'lbb.checkout.enabled' => true, 'lbb.payment.enabled' => true,
            'lbb.payment.provider' => 'zarinpal', 'lbb.payment.zarinpal.merchant_id' => 'placeholder',
            'lbb.payment.zarinpal.sandbox' => false, 'app.frontend_url' => 'https://nox.example.test',
            'lbb.payment.callback_url' => 'https://nox.example.test/payment/result',
            'lbb.checkout.delivery_methods.tipax.enabled' => true,
        ]);
        $this->artisan('nox:services')->assertExitCode(0);
        Http::assertNothingSent();
    }

    public function test_unknown_service_is_rejected(): void
    {
        $this->artisan('nox:services', ['--service' => 'unknown'])->assertExitCode(2);
    }
}
