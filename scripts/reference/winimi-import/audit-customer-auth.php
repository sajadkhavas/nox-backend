<?php

$errors = [];

$files = [
    'migration' => 'database/migrations/2026_07_19_163000_create_customer_auth_tables.php',
    'customer' => 'app/Models/Customer.php',
    'challenge' => 'app/Models/OtpChallenge.php',
    'mobile' => 'app/Support/IranianMobile.php',
    'service' => 'app/Services/Auth/OtpService.php',
    'sender' => 'app/Services/Auth/OtpSender.php',
    'controller' => 'app/Http/Controllers/Api/OtpAuthController.php',
    'account' => 'app/Http/Controllers/Api/AccountController.php',
    'resource' => 'app/Http/Resources/CustomerResource.php',
    'activeMiddleware' => 'app/Http/Middleware/EnsureActiveCustomer.php',
    'customerAdmin' => 'app/Filament/Resources/CustomerResource.php',
    'bootstrap' => 'bootstrap/app.php',
    'authConfig' => 'config/auth.php',
    'sanctumConfig' => 'config/sanctum.php',
    'winimiConfig' => 'config/winimi.php',
    'routes' => 'routes/api.php',
    'provider' => 'app/Providers/AppServiceProvider.php',
    'schedule' => 'routes/console.php',
    'env' => '.env.example',
    'tests' => 'tests/Feature/CustomerOtpAuthTest.php',
];

$sources = [];
foreach ($files as $name => $path) {
    if (! is_file($path)) {
        $errors[] = "Missing required customer-auth file: {$path}";

        continue;
    }

    $sources[$name] = (string) file_get_contents($path);
}

$requireText = static function (string $source, string $needle, string $label) use (&$errors, &$sources, $files): void {
    if (! isset($sources[$source]) || ! str_contains($sources[$source], $needle)) {
        $errors[] = ($files[$source] ?? $source).": missing {$label}";
    }
};

$requirePattern = static function (string $source, string $pattern, string $label) use (&$errors, &$sources, $files): void {
    if (! isset($sources[$source]) || preg_match($pattern, $sources[$source]) !== 1) {
        $errors[] = ($files[$source] ?? $source).": missing {$label}";
    }
};

$forbidText = static function (string $source, string $needle, string $label) use (&$errors, &$sources, $files): void {
    if (isset($sources[$source]) && str_contains($sources[$source], $needle)) {
        $errors[] = ($files[$source] ?? $source).": contains forbidden {$label}";
    }
};

foreach (['customers', 'otp_challenges', 'mobile_hash', 'code_hash', 'expires_at', 'consumed_at'] as $field) {
    $requireText('migration', $field, "auth schema field {$field}");
}

$requirePattern('migration', "/char\('public_id',\s*26\)->unique\(\)/", 'non-sequential public IDs');
$requirePattern('challenge', "/'mobile_payload'\s*=>\s*'encrypted'/", 'encrypted mobile challenge payload');
$requireText('service', 'Hash::make($code)', 'hashed OTP storage');
$requireText('service', 'Hash::check($code, $challenge->code_hash)', 'OTP hash verification');
$requirePattern('service', '/\$challenge->attempts\s*\+\+/', 'failed-attempt counter');
$requirePattern('service', "/'consumed_at'\s*=>\s*now\(\)/", 'one-time challenge consumption');
$requirePattern('service', "/app\(\)->environment\(\[\s*'local',\s*'testing'\s*\]\)/", 'test-code production guard');
$forbidText('service', 'Log::', 'OTP logging');
$forbidText('sender', 'logger(', 'OTP logging');
$forbidText('sender', 'report(', 'provider exception reporting that can expose credential-bearing URLs');

$requirePattern('authConfig', "/'customer'\s*=>\s*\[\s*'driver'\s*=>\s*'session',\s*'provider'\s*=>\s*'customers'\s*\]/s", 'isolated customer session guard');
$requirePattern('sanctumConfig', "/'guard'\s*=>\s*\[\s*'customer',\s*'web'\s*\]/s", 'customer Sanctum guard');
$requireText('controller', "Auth::guard('customer')->login", 'customer guard login');
$requireText('controller', '$request->session()->regenerate()', 'session rotation after login');
$requireText('controller', '$request->session()->invalidate()', 'session invalidation on logout');
$requirePattern('bootstrap', "/'customer\.active'\s*=>\s*EnsureActiveCustomer::class/", 'active customer middleware alias');
$requireText('activeMiddleware', "Auth::guard('customer')->logout()", 'disabled customer logout');
$requireText('activeMiddleware', '$request->session()->invalidate()', 'disabled customer session invalidation');
$requirePattern('routes', "/\[\s*'auth:customer',\s*'customer\.active',\s*'throttle:60,1'\s*\]/", 'active protected customer endpoints');
$requireText('routes', 'throttle:otp-request', 'OTP request limiter');
$requireText('routes', 'throttle:otp-verify', 'OTP verification limiter');
$requireText('provider', "RateLimiter::for('otp-request'", 'mobile/IP request rate limiter');
$requireText('provider', "RateLimiter::for('otp-verify'", 'challenge/IP verify rate limiter');
$requireText('schedule', 'prune-otp-challenges', 'OTP challenge pruning schedule');

$requirePattern('customerAdmin', "/navigationLabel\s*=\s*'مشتریان'/u", 'customer administration resource');
$requireText('customerAdmin', "TextInput::make('mobile')", 'customer mobile display');
$requireText('customerAdmin', '->disabled()', 'read-only customer identity fields');
$forbidText('customerAdmin', 'CreateCustomer', 'manual customer creation');
$forbidText('customerAdmin', 'DeleteBulkAction', 'bulk customer deletion');

$requirePattern('winimiConfig', "/'authentication'\s*=>\s*\[\s*'status'\s*=>\s*'implemented'/s", 'implemented authentication contract');
$requirePattern('winimiConfig', "/'orders'\s*=>\s*\[\s*'status'\s*=>\s*'implemented'/s", 'implemented order contract');
$requirePattern('winimiConfig', "/'payments'\s*=>\s*\[\s*'status'\s*=>\s*'implemented'/s", 'implemented payment contract');
$requireText('env', 'SMS_PROVIDER=disabled', 'secure default SMS provider');
$requireText('env', 'OTP_EXPOSE_TEST_CODE=false', 'secure test-code default');
$requireText('env', 'SESSION_ENCRYPT=true', 'encrypted session default');

foreach ([
    'test_otp_request_normalizes_mobile_and_never_stores_plain_code_or_mobile_payload',
    'test_customer_can_verify_otp_use_session_update_profile_and_logout',
    'test_disabling_customer_invalidates_an_existing_session',
    'test_wrong_code_increments_attempts_and_consumed_code_cannot_be_reused',
    'test_challenge_is_locked_after_maximum_failed_attempts',
    'test_disabled_provider_returns_service_unavailable_and_removes_challenge',
] as $testName) {
    $requireText('tests', $testName, "security regression test {$testName}");
}

if ($errors !== []) {
    fwrite(STDERR, "Customer authentication audit failed:\n- ".implode("\n- ", $errors)."\n");
    exit(1);
}

echo 'Customer authentication audit passed: isolated sessions, OTP storage, rate limits and contract boundaries verified.'.PHP_EOL;
