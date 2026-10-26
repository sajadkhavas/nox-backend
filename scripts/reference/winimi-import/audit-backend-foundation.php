<?php

$errors = [];
$warnings = [];

$read = static function (string $path) use (&$errors): string {
    if (! is_file($path)) {
        $errors[] = "Missing required file: {$path}";

        return '';
    }

    return (string) file_get_contents($path);
};

$requireText = static function (string $path, string $needle, string $description) use (&$errors, $read): void {
    if (! str_contains($read($path), $needle)) {
        $errors[] = "{$path}: missing {$description}";
    }
};

$forbidText = static function (string $path, string $needle, string $description) use (&$errors, $read): void {
    if (str_contains($read($path), $needle)) {
        $errors[] = "{$path}: contains forbidden {$description}";
    }
};

$requiredFiles = [
    '.env.example',
    'config/winimi.php',
    'app/Support/ApiResponse.php',
    'app/Http/Middleware/AttachApiContext.php',
    'app/Http/Middleware/MarkLegacyApi.php',
    'app/Http/Controllers/Api/SystemController.php',
    'docs/API_CONTRACT.md',
    'docs/BACKEND_AUDIT.md',
    'docs/PAYMENTS.md',
    'tests/Feature/SystemApiTest.php',
    'tests/Unit/BackendFoundationTest.php',
];

foreach ($requiredFiles as $file) {
    if (! is_file($file)) {
        $errors[] = "Missing required file: {$file}";
    }
}

$requireText('routes/api.php', "Route::prefix('system')", 'system route group');
$requireText('routes/api.php', "middleware('api.legacy')", 'legacy route boundary');
$requireText('bootstrap/app.php', '$middleware->statefulApi();', 'Sanctum stateful API middleware');
$requireText('bootstrap/app.php', 'shouldRenderJsonWhen', 'JSON API exception rendering');
$requireText('config/cors.php', "'supports_credentials' => true", 'credentialed CORS');
$requireText('config/cors.php', "env('FRONTEND_URLS'", 'environment-driven frontend origins');
$requireText('app/Models/Product.php', "config('winimi.brand.name_en'", 'Winimi product seller identity');
$requireText('README.md', 'No production administrator password or external credential is committed.', 'administrator and external credential policy');
$requireText('docs/API_CONTRACT.md', '/api/auth/otp/request', 'frontend OTP contract');
$requireText('docs/API_CONTRACT.md', '/api/checkout', 'frontend checkout contract');
$requireText('docs/API_CONTRACT.md', '/api/payments/zarinpal/verify', 'frontend payment verification contract');
$requireText('docs/PAYMENTS.md', 'Only provider verification may mark an order paid.', 'server-authoritative payment policy');

$forbidText('composer.json', 'toolmaster/backend', 'ToolMaster Composer package identity');
$forbidText('config/cors.php', 'toolmaster.com', 'ToolMaster production origin');
$forbidText('app/Models/Product.php', "'name' => 'ToolMaster'", 'ToolMaster schema seller');
$forbidText('README.md', 'Admin@2025!Change', 'committed default administrator password');
$forbidText('.env.example', 'VITE_ZARINPAL', 'frontend payment secret');
$forbidText('.env.example', 'VITE_KAVENEGAR', 'frontend SMS secret');

$env = $read('.env.example');
foreach (['KAVENEGAR_API_KEY=', 'ZARINPAL_MERCHANT_ID='] as $emptySecret) {
    if (! str_contains($env, $emptySecret)) {
        $errors[] = ".env.example: missing empty server-only secret placeholder {$emptySecret}";
    }
}

$legacyFrontendFiles = ['src', 'vite.config.ts', 'package.json'];
foreach ($legacyFrontendFiles as $path) {
    if (file_exists($path)) {
        $warnings[] = "Legacy frontend artifact remains for dependency review: {$path}";
    }
}

if ($warnings !== []) {
    fwrite(STDOUT, 'Backend foundation warnings ('.count($warnings)."):\n");
    foreach ($warnings as $warning) {
        fwrite(STDOUT, "- {$warning}\n");
    }
}

if ($errors !== []) {
    fwrite(STDERR, 'Backend foundation audit failed with '.count($errors)." issue(s):\n");
    foreach ($errors as $error) {
        fwrite(STDERR, "- {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, 'Backend foundation audit passed with '.count($warnings)." documented warning(s).\n");
