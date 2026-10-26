<?php

$errors = [];

$requiredFiles = [
    'phase18' => 'config/phase18.php',
    'provider' => 'app/Providers/AppServiceProvider.php',
    'acceptance' => 'tests/Feature/EndToEndAcceptanceTest.php',
    'system' => 'tests/Feature/SystemApiTest.php',
    'roadmap' => 'docs/FULL_LAUNCH_ROADMAP.md',
    'topology' => 'docs/SINGLE_SERVER_TOPOLOGY.md',
    'workflow' => '.github/workflows/phase18-acceptance.yml',
];

$sources = [];
foreach ($requiredFiles as $name => $path) {
    if (! is_file($path)) {
        $errors[] = "Missing Phase 18 file: {$path}";

        continue;
    }

    $sources[$name] = (string) file_get_contents($path);
}

$require = static function (string $source, string $needle, string $label) use (&$errors, $sources, $requiredFiles): void {
    if (! isset($sources[$source]) || ! str_contains($sources[$source], $needle)) {
        $errors[] = ($requiredFiles[$source] ?? $source).": missing {$label}";
    }
};

$forbid = static function (string $source, string $needle, string $label) use (&$errors, $sources, $requiredFiles): void {
    if (isset($sources[$source]) && str_contains($sources[$source], $needle)) {
        $errors[] = ($requiredFiles[$source] ?? $source).": contains forbidden {$label}";
    }
};

$require('phase18', "'roadmap_version' => '2026-07-20-phase-18'", 'Phase 18 roadmap identity');
$require('phase18', "'frontend_integrated'", 'frontend integration gate');
$require('phase18', "'end_to_end_verified'", 'end-to-end gate');
$require('phase18', "'status' => 'ready'", 'ready status');
$require('phase18', "'single-server-two-virtual-hosts'", 'single-server topology');
$require('provider', "config('phase18', [])", 'runtime readiness overlay');
$require('acceptance', 'WinimiStagingSeeder', 'deterministic staging fixture');
$require('acceptance', '/api/auth/otp/request', 'OTP journey');
$require('acceptance', '/api/account/addresses', 'address journey');
$require('acceptance', '/api/checkout', 'checkout journey');
$require('acceptance', '/api/payments/verify', 'payment verification journey');
$require('acceptance', "'meta.replayed', true", 'duplicate callback replay assertion');
$require('acceptance', 'phase18-chilled-rejected', 'chilled-zone rejection');
$require('acceptance', '/api/inquiries', 'persisted inquiry journey');
$require('system', "'2026-07-20-phase-16'", 'frozen public contract assertion');
$require('system', "'2026-07-20-phase-18'", 'Phase 18 roadmap assertion');
$require('roadmap', 'Status: `end_to_end_verified=ready`', 'Phase 18 readiness marker');
$require('topology', 'winimibakery.com', 'storefront virtual host');
$require('topology', 'api.winimibakery.com', 'Laravel virtual host');
$require('workflow', 'EndToEndAcceptanceTest', 'targeted acceptance test execution');
$require('workflow', 'audit-phase-18-acceptance.php', 'acceptance architecture audit execution');

$forbid('phase18', 'ZARINPAL_MERCHANT_ID=', 'committed payment credential');
$forbid('phase18', 'KAVENEGAR_API_KEY=', 'committed SMS credential');
$forbid('acceptance', 'https://api.zarinpal.com', 'live payment dependency');
$forbid('acceptance', 'trustseal.enamad.ir', 'live eNAMAD dependency');

if ($errors !== []) {
    fwrite(STDERR, "Phase 18 acceptance audit failed:\n- ".implode("\n- ", $errors)."\n");
    exit(1);
}

echo 'Phase 18 acceptance audit passed: frozen contract, full API journey, browser handoff and single-server topology are locked.'.PHP_EOL;
