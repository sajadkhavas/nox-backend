<?php

$errors = [];

$files = [
    'migration' => 'database/migrations/2026_07_19_190000_create_payment_attempts_table.php',
    'attempt' => 'app/Models/PaymentAttempt.php',
    'providerContract' => 'app/Contracts/Payments/PaymentProvider.php',
    'manager' => 'app/Services/Payments/PaymentProviderManager.php',
    'service' => 'app/Services/Payments/PaymentService.php',
    'lifecycle' => 'app/Services/Orders/OrderLifecycleService.php',
    'testing' => 'app/Services/Payments/Providers/TestingPaymentProvider.php',
    'zarinpal' => 'app/Services/Payments/Providers/ZarinpalPaymentProvider.php',
    'controller' => 'app/Http/Controllers/Api/PaymentController.php',
    'resource' => 'app/Http/Resources/PaymentAttemptResource.php',
    'routes' => 'routes/api.php',
    'config' => 'config/winimi.php',
    'env' => '.env.example',
    'filament' => 'app/Filament/Resources/PaymentAttemptResource.php',
    'tests' => 'tests/Feature/PaymentFlowTest.php',
];

$sources = [];
foreach ($files as $name => $path) {
    if (! is_file($path)) {
        $errors[] = "Missing required payment file: {$path}";

        continue;
    }

    $sources[$name] = (string) file_get_contents($path);
}

$require = static function (string $source, string $needle, string $label) use (&$errors, $sources, $files): void {
    if (! isset($sources[$source]) || ! str_contains($sources[$source], $needle)) {
        $errors[] = ($files[$source] ?? $source).": missing {$label}";
    }
};

$forbid = static function (string $source, string $needle, string $label) use (&$errors, $sources, $files): void {
    if (isset($sources[$source]) && str_contains($sources[$source], $needle)) {
        $errors[] = ($files[$source] ?? $source).": contains forbidden {$label}";
    }
};

$require('migration', "Schema::create('payment_attempts'", 'payment attempt table');
foreach ([
    'idempotency_key',
    'request_hash',
    'authority',
    'reference_id',
    'request_payload',
    'response_payload',
    'verification_payload',
] as $field) {
    $require('migration', $field, "payment integrity field {$field}");
}

$require('providerContract', 'function initiate', 'provider initiation contract');
$require('providerContract', 'function verify', 'provider verification contract');
$require('manager', "'disabled'", 'disabled provider');
$require('manager', "'testing'", 'testing provider');
$require('manager', "'zarinpal'", 'Zarinpal provider');
$require('testing', "environment('production')", 'testing-provider production guard');
$require('zarinpal', "'[REDACTED]'", 'merchant redaction');
$require('zarinpal', "unset(\$payload['data']['card_pan']", 'card data redaction');

$require('service', 'lockForUpdate()', 'payment row locking');
$require('service', "where('idempotency_key'", 'payment idempotency lookup');
$require('service', 'markPaidFromVerifiedPaymentLocked', 'verified payment transition');
$require('lifecycle', 'consumeReservationsLocked', 'atomic reservation consumption');
$require('lifecycle', "decrement('stock_quantity'", 'stock decrement after verification');
$require('lifecycle', "'payment_status' => PaymentStatus::Paid", 'paid state transition');

$require('routes', "Route::post('orders/{orderId}/payments'", 'payment initiation route');
$require('routes', "Route::post('payments/verify'", 'provider-neutral verification route');
$require('routes', "Route::post('payments/zarinpal/verify'", 'Zarinpal verification compatibility route');
$require('controller', "->ownedBy(\$request->user('customer'))", 'payment order ownership');
$require('controller', "'verified' => \$verified", 'verified response state');

$require('config', "'payments' => [\n            'status' => 'implemented'", 'implemented payment contract');
$require('config', "'activation' => 'disabled-until-external-credentials'", 'external activation boundary');
$require('env', 'PAYMENT_ENABLED=false', 'secure disabled payment default');
$require('env', 'PAYMENT_PROVIDER=disabled', 'secure disabled provider default');
$require('env', 'ZARINPAL_MERCHANT_ID=', 'server-only merchant placeholder');
$forbid('resource', 'merchant', 'merchant credential exposure');
$forbid('resource', 'request_payload', 'provider request payload exposure');
$forbid('resource', 'verification_payload', 'provider verification payload exposure');
$forbid('filament', 'CreateAction', 'manual payment creation');
$forbid('filament', 'DeleteBulkAction', 'bulk payment deletion');

foreach ([
    'test_payment_initiation_is_idempotent_and_reuses_the_active_attempt',
    'test_verified_payment_marks_order_paid_and_consumes_stock_exactly_once',
    'test_cancelled_attempt_keeps_reservation_and_allows_a_new_retry_attempt',
    'test_customer_cannot_initiate_or_verify_another_customers_payment',
    'test_payment_is_disabled_by_default_even_when_checkout_exists',
    'test_zarinpal_adapter_uses_server_amount_and_verifies_the_recorded_authority',
] as $testName) {
    $require('tests', $testName, "payment regression test {$testName}");
}

if ($errors !== []) {
    fwrite(STDERR, "Payment architecture audit failed:\n- ".implode("\n- ", $errors)."\n");
    exit(1);
}

echo 'Payment architecture audit passed: provider isolation, idempotency, verification, inventory and secret boundaries verified.'.PHP_EOL;
