<?php

$errors = [];

$read = static function (string $path) use (&$errors): string {
    if (! is_file($path)) {
        $errors[] = "Missing Phase 16 file: {$path}";

        return '';
    }

    return (string) file_get_contents($path);
};

$requireText = static function (string $path, string $needle, string $label) use (&$errors, $read): void {
    if (! str_contains($read($path), $needle)) {
        $errors[] = "{$path}: missing {$label}";
    }
};

$forbidText = static function (string $path, string $needle, string $label) use (&$errors, $read): void {
    if (str_contains($read($path), $needle)) {
        $errors[] = "{$path}: contains forbidden {$label}";
    }
};

$requiredFiles = [
    'docs/openapi.json',
    'docs/API_ERRORS_AND_PAGINATION.md',
    'docs/OPERATIONS_POLICIES.md',
    'docs/BACKUP_RESTORE.md',
    'docs/QUERY_INDEX_REVIEW.md',
    'app/Enums/ApiErrorCode.php',
    'app/Support/ApiExceptionRenderer.php',
    'app/Support/Pagination.php',
    'app/Console/Commands/BackendReadiness.php',
    'database/migrations/2026_07_20_120000_optimize_backend_contract_indexes.php',
    'database/seeders/WinimiStagingSeeder.php',
    'tests/Feature/BackendContractFreezeTest.php',
];

foreach ($requiredFiles as $file) {
    $read($file);
}

try {
    $openapi = json_decode($read('docs/openapi.json'), true, flags: JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    $errors[] = 'docs/openapi.json: invalid JSON: '.$exception->getMessage();
    $openapi = [];
}

if (($openapi['openapi'] ?? null) !== '3.1.0') {
    $errors[] = 'docs/openapi.json: OpenAPI version must be 3.1.0';
}
if (($openapi['info']['version'] ?? null) !== '2026-07-20-phase-16') {
    $errors[] = 'docs/openapi.json: contract version must be Phase 16';
}

$expectedPaths = [
    '/api/system/health',
    '/api/system/ready',
    '/api/system/meta',
    '/api/system/contracts',
    '/api/system/openapi',
    '/api/catalog/categories',
    '/api/catalog/products',
    '/api/catalog/products/{slug}',
    '/api/catalog/products/{slug}/reviews',
    '/api/delivery/options',
    '/api/store/settings',
    '/api/store/pages/{slug}',
    '/api/store/faqs',
    '/api/store/gallery',
    '/api/store/posts',
    '/api/store/posts/{slug}',
    '/api/store/cities/{slug}',
    '/api/inquiries',
    '/api/auth/otp/request',
    '/api/auth/otp/verify',
    '/api/auth/me',
    '/api/auth/logout',
    '/api/account/profile',
    '/api/account/addresses',
    '/api/account/addresses/{addressId}',
    '/api/checkout',
    '/api/account/orders',
    '/api/account/orders/{orderId}',
    '/api/account/orders/{orderId}/cancel',
    '/api/account/orders/{orderId}/reviews',
    '/api/orders/{orderId}/payments',
    '/api/payments/verify',
    '/api/payments/zarinpal/verify',
];

foreach ($expectedPaths as $path) {
    if (! isset($openapi['paths'][$path])) {
        $errors[] = "docs/openapi.json: missing path {$path}";
    }
}

foreach (array_keys($openapi['paths'] ?? []) as $path) {
    if (str_starts_with($path, '/api/v1/')) {
        $errors[] = "docs/openapi.json: legacy path must not be frozen as Winimi contract: {$path}";
    }
}

foreach ([
    'validation_failed',
    'authentication_required',
    'resource_not_found',
    'legacy_api_disabled',
    'conflict',
    'rate_limited',
    'internal_error',
] as $errorCode) {
    $requireText('app/Enums/ApiErrorCode.php', "'{$errorCode}'", "error code {$errorCode}");
}

$requireText('app/Support/ApiResponse.php', "'contractVersion'", 'contract version response metadata');
$requireText('app/Support/ApiResponse.php', "'code' =>", 'stable error code field');
$requireText('app/Support/ApiExceptionRenderer.php', 'ValidationException', 'validation exception mapping');
$requireText('app/Support/ApiExceptionRenderer.php', 'AuthenticationException', 'authentication exception mapping');
$requireText('app/Support/ApiExceptionRenderer.php', 'ModelNotFoundException', 'not-found exception mapping');
$requireText('bootstrap/app.php', 'ApiExceptionRenderer::render', 'central API exception renderer');
$requireText('app/Support/Pagination.php', "'hasMore'", 'frozen pagination hasMore field');
$requireText('routes/api.php', "Route::get('openapi'", 'OpenAPI system route');
$requireText('app/Http/Controllers/Api/SystemController.php', "base_path('docs/openapi.json')", 'OpenAPI document source');

$requireText('config/winimi.php', "'contract_version' => '2026-07-20-phase-16'", 'Phase 16 contract identity');
$requireText('config/winimi.php', "'backend_freeze' => [", 'backend freeze contract');
$requireText('config/winimi.php', "'backend_complete' => [\n                'status' => 'ready'", 'ready backend gate');
$requireText('config/winimi.php', "'production_default' => false", 'disabled production legacy default');
$requireText('config/winimi.php', "'policies' => [", 'operations policies');
$requireText('app/Http/Middleware/MarkLegacyApi.php', 'ApiErrorCode::LegacyApiDisabled', 'legacy disabled error code');

foreach ([
    'orders_customer_status_placed_index',
    'orders_operations_status_index',
    'payment_attempt_customer_status_index',
    'payment_attempt_expiry_status_index',
    'bakery_posts_public_contract_index',
    'inquiries_operations_contract_index',
] as $index) {
    $requireText(
        'database/migrations/2026_07_20_120000_optimize_backend_contract_indexes.php',
        $index,
        "reviewed index {$index}",
    );
}

$requireText('database/seeders/WinimiStagingSeeder.php', "environment('production')", 'production staging-seed guard');
$requireText('database/seeders/WinimiStagingSeeder.php', 'staging-chocolate-cookie', 'dry staging product');
$requireText('database/seeders/WinimiStagingSeeder.php', 'staging-chilled-cake', 'chilled staging product');
$requireText('database/seeders/WinimiStagingSeeder.php', "'value' => '0'", 'disabled eNAMAD staging state');
$requireText('database/seeders/DatabaseSeeder.php', 'SEED_WINIMI_STAGING', 'opt-in staging seeding');
$requireText('.env.example', 'SEED_WINIMI_STAGING=false', 'safe staging seed default');
$requireText('.env.example', 'BACKUP_RETENTION_DAYS=14', 'backup retention configuration');
$requireText('.env.example', 'MEDIA_DISK=public', 'media disk configuration');

foreach ([
    'test_openapi_and_machine_readable_backend_gate_are_frozen',
    'test_errors_use_frozen_codes_and_contract_metadata',
    'test_paginated_surfaces_use_the_same_metadata_shape',
    'test_owned_resources_do_not_disclose_cross_customer_records',
    'test_staging_seeder_is_idempotent_and_keeps_external_integrations_disabled',
] as $test) {
    $requireText('tests/Feature/BackendContractFreezeTest.php', $test, "Phase 16 regression {$test}");
}

$requireText('.github/workflows/backend-ci.yml', 'php scripts/audit-backend-freeze.php', 'CI backend freeze audit');
$requireText('.github/workflows/backend-ci.yml', 'composer format:check', 'read-only existing Pint validation');
$requireText('.github/workflows/backend-ci.yml', 'php vendor/bin/pint --test', 'read-only Phase 16 Pint validation');
$requireText('.github/workflows/backend-ci.yml', 'php artisan backend:readiness --json', 'executable readiness gate');
$forbidText('.github/workflows/backend-ci.yml', 'Apply canonical Pint formatting', 'CI source mutation');
$forbidText('.github/workflows/backend-ci.yml', 'pint-formatted-files', 'temporary Pint artifact workflow');

if ($errors !== []) {
    fwrite(STDERR, "Backend contract freeze audit failed:\n- ".implode("\n- ", $errors)."\n");
    exit(1);
}

echo 'Backend contract freeze audit passed: OpenAPI, errors, pagination, IDOR, policies, indexes, staging and readiness are locked.'.PHP_EOL;
