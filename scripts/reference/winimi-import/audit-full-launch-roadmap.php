<?php

$errors = [];

$requiredFiles = [
    'roadmap' => 'docs/FULL_LAUNCH_ROADMAP.md',
    'config' => 'config/winimi.php',
    'system' => 'app/Http/Controllers/Api/SystemController.php',
    'test' => 'tests/Feature/SystemApiTest.php',
];

$sources = [];
foreach ($requiredFiles as $name => $path) {
    if (! is_file($path)) {
        $errors[] = "Missing full-launch file: {$path}";

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

foreach ([
    'Phase 14 — Provider-ready payment backend',
    'Phase 15 — Complete store operations backend',
    'Phase 16 — Backend completion and contract freeze',
    'Phase 17 — Full frontend/backend integration',
    'Phase 18 — End-to-end completion',
    'Phase 19A — Production deployment package',
    'Phase 19B — Live server execution',
    'Phase 20 — External activation only',
] as $phase) {
    $require('roadmap', $phase, "locked roadmap section {$phase}");
}

foreach ([
    "'backend_complete'",
    "'frontend_integrated'",
    "'end_to_end_verified'",
    "'production_deployed'",
    "'payment_gateway_credentials'",
    "'enamad_badge_code'",
    "'sms_provider_credentials'",
] as $gate) {
    $require('config', $gate, "machine-readable gate {$gate}");
}

$require('roadmap', 'production_server_package=ready', 'Phase 19A package marker');
$require('roadmap', 'production_deployed=ready', 'Phase 19B live marker');
$require('config', "'strategy' => 'complete-internal-work-before-external-activation'", 'locked completion strategy');
$require('system', "'launch' => config('winimi.launch', [])", 'launch gate API exposure');
$require('test', "->assertJsonCount(3, 'data.launch.external_only')", 'exact external dependency count test');

foreach ([
    'payment gateway credentials / Zarinpal Merchant ID',
    'eNAMAD badge code',
    'SMS provider API key and approved OTP template',
] as $externalInput) {
    $require('roadmap', $externalInput, "external-only input {$externalInput}");
}

$forbid('roadmap', 'four external inputs', 'additional external dependency count');

if ($errors !== []) {
    fwrite(STDERR, "Full-launch roadmap audit failed:\n- ".implode("\n- ", $errors)."\n");
    exit(1);
}

echo 'Full-launch roadmap audit passed: backend completion, Phase 19A package, Phase 19B live deployment and exactly three external activations are locked.'.PHP_EOL;
