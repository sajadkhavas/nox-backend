<?php

namespace App\Console\Commands;

use App\Support\NoxServiceEnvironment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class NoxServices extends Command
{
    protected $signature = 'nox:services {--configure : Enter and save NOX account credentials interactively} {--service=all : all, sms, or payment}';

    protected $description = 'Configure NOX Kavenegar/Zarinpal accounts or check local service readiness without sending SMS or charging a customer';

    public function handle(): int
    {
        $service = (string) $this->option('service');
        if (! in_array($service, ['all', 'sms', 'payment'], true)) {
            $this->error('Use --service=all, sms, or payment.');
            return self::INVALID;
        }
        if (! $this->option('configure')) {
            return $this->check($service);
        }
        if (! $this->input->isInteractive()) {
            $this->error('Configure interactively. Never pass account secrets as command-line arguments.');
            return self::FAILURE;
        }
        $path = app()->environmentFilePath();
        if (! File::isFile($path)) {
            $this->error('Create the NOX .env first; keep the existing APP_KEY and database credentials.');
            return self::FAILURE;
        }
        $values = [];
        if (in_array($service, ['all', 'sms'], true)) {
            $key = trim((string) $this->secret('NOX Kavenegar API key'));
            $template = trim((string) $this->ask('Approved Kavenegar OTP template name (%token)'));
            if ($key === '' || $template === '') {
                $this->error('API key and approved OTP template name are required; no changes saved.');
                return self::FAILURE;
            }
            $sender = trim((string) $this->ask('Order SMS sender (leave empty to keep order SMS disabled)', ''));
            $values += [
                'SMS_PROVIDER' => 'kavenegar', 'KAVENEGAR_API_KEY' => $key,
                'KAVENEGAR_TEMPLATE' => $template, 'OTP_EXPOSE_TEST_CODE' => 'false',
                'ORDER_SMS_PROVIDER' => $sender !== '' ? 'kavenegar' : 'disabled',
                'KAVENEGAR_ORDER_SENDER' => $sender,
            ];
        }
        if (in_array($service, ['all', 'payment'], true)) {
            $merchant = trim((string) $this->secret('NOX Zarinpal Merchant ID'));
            $origin = rtrim(trim((string) $this->ask('NOX frontend HTTPS origin', config('app.frontend_url'))), '/');
            $parts = parse_url($origin);
            if (! preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/iD', $merchant)
                || ! $parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
                || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
                || ! in_array($parts['path'] ?? '', ['', '/'], true)) {
                $this->error('A valid Merchant ID and clean HTTPS frontend origin are required; no changes saved.');
                return self::FAILURE;
            }
            $cookieDomain = preg_replace('/^www\./', '', $parts['host']);
            $backend = parse_url((string) config('app.url'));
            $backendHost = $backend['host'] ?? '';
            if (($backend['scheme'] ?? '') !== 'https'
                || ($backendHost !== $cookieDomain && ! str_ends_with($backendHost, '.'.$cookieDomain))) {
                $this->error('Configure the NOX HTTPS APP_URL on the same domain as the frontend before payment activation; no changes saved.');
                return self::FAILURE;
            }
            $values += [
                'PAYMENT_ENABLED' => 'true', 'PAYMENT_PROVIDER' => 'zarinpal',
                'ZARINPAL_MERCHANT_ID' => $merchant, 'ZARINPAL_SANDBOX' => 'false',
                'PAYMENT_CALLBACK_URL' => $origin.'/payment/result',
                'PAYMENT_CURRENCY' => 'IRR', 'PAYMENT_AMOUNT_MULTIPLIER' => '10',
                'FRONTEND_URL' => $origin, 'FRONTEND_URLS' => $origin,
                'SANCTUM_STATEFUL_DOMAINS' => $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : ''),
                'SESSION_SECURE_COOKIE' => 'true',
                'SESSION_DOMAIN' => '.'.$cookieDomain,
                'CHECKOUT_ENABLED' => 'true', 'DELIVERY_TIPAX_ENABLED' => 'true',
                'ZARINPAL_REQUEST_URL' => 'https://api.zarinpal.com/pg/v4/payment/request.json',
                'ZARINPAL_VERIFY_URL' => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
                'ZARINPAL_START_PAY_URL' => 'https://www.zarinpal.com/pg/StartPay',
            ];
        }
        try {
            $old = File::get($path);
            $updated = NoxServiceEnvironment::update($old, $values);
            $directory = storage_path('app/private/nox-services');
            File::ensureDirectoryExists($directory, 0700);
            File::replace($directory.'/environment-'.bin2hex(random_bytes(8)).'.backup', $old, 0600);
            File::replace($path, $updated, 0600);
        } catch (Throwable) {
            $this->error('Could not safely save the service configuration. No credential values are printed.');
            return self::FAILURE;
        }
        \Dotenv\Dotenv::createMutable(app()->environmentPath(), app()->environmentFile())->load();
        if ($this->call('config:cache') !== self::SUCCESS) {
            $this->error('Configuration saved; config:cache failed. Resolve it before using the services.');
            return self::FAILURE;
        }
        if ($this->call('queue:restart') !== self::SUCCESS) {
            $this->error('Configuration saved; the queue restart signal failed. Check cache/worker setup.');
            return self::FAILURE;
        }
        $this->info('NOX account configuration saved. Existing APP_KEY and database settings were preserved.');
        $this->warn('This does not confirm provider account approval, SMS delivery or a real payment.');
        return self::SUCCESS;
    }

    private function check(string $service): int
    {
        $checks = [];
        if (in_array($service, ['all', 'sms'], true)) {
            $checks['OTP provider is Kavenegar'] = config('lbb.otp.provider') === 'kavenegar';
            $checks['Kavenegar API key is configured'] = trim((string) config('lbb.otp.kavenegar.api_key')) !== '';
            $checks['OTP template is configured'] = trim((string) config('lbb.otp.kavenegar.template')) !== '';
            $checks['Test OTP is hidden'] = ! config('lbb.otp.expose_test_code');
        }
        if (in_array($service, ['all', 'payment'], true)) {
            $checks['Checkout is enabled'] = (bool) config('lbb.checkout.enabled');
            $checks['Payment is enabled'] = (bool) config('lbb.payment.enabled');
            $checks['Payment provider is Zarinpal'] = config('lbb.payment.provider') === 'zarinpal';
            $checks['Merchant ID is configured'] = trim((string) config('lbb.payment.zarinpal.merchant_id')) !== '';
            $checks['Production gateway is selected'] = ! config('lbb.payment.zarinpal.sandbox');
            $checks['Callback matches frontend'] = config('lbb.payment.callback_url') === rtrim((string) config('app.frontend_url'), '/').'/payment/result';
            $checks['Tipax is enabled'] = (bool) config('lbb.checkout.delivery_methods.tipax.enabled');
        }
        $this->table(['Local configuration check', 'Status'], collect($checks)->map(static fn ($ok, $name) => [$name, $ok ? 'configured' : 'missing'])->values()->all());
        $this->line('Local checks only: no provider request, SMS or payment was sent.');
        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
