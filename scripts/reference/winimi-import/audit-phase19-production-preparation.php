<?php

declare(strict_types=1);

$requiredFiles = [
    'deploy/backend.production.env.example',
    'deploy/nginx/winimi-api.conf.example',
    'deploy/systemd/winimi-backend-queue.service',
    'deploy/systemd/winimi-backend-scheduler.service',
    'deploy/systemd/winimi-backend-scheduler.timer',
    'deploy/systemd/winimi-backend-backup.service',
    'deploy/systemd/winimi-backend-backup.timer',
    'deploy/bin/deploy-backend.sh',
    'deploy/bin/deploy-production-backend.sh',
    'deploy/bin/rollback-backend.sh',
    'deploy/bin/preflight-backend-server.sh',
    'deploy/bin/smoke-backend-production.sh',
    'scripts/create-backend-release.php',
    'scripts/verify-backend-release.php',
    'docs/PHASE_19_PRODUCTION_DEPLOYMENT.md',
];

$failures = [];
foreach ($requiredFiles as $file) {
    if (! is_file($file)) {
        $failures[] = "missing required Phase 19 file: {$file}";
    }
}

$read = static fn (string $file): string => is_file($file) ? (string) file_get_contents($file) : '';
$env = $read('deploy/backend.production.env.example');
$nginx = $read('deploy/nginx/winimi-api.conf.example');
$queue = $read('deploy/systemd/winimi-backend-queue.service');
$scheduler = $read('deploy/systemd/winimi-backend-scheduler.timer');
$backup = $read('deploy/systemd/winimi-backend-backup.service');
$deploy = $read('deploy/bin/deploy-backend.sh');
$productionDeploy = $read('deploy/bin/deploy-production-backend.sh');
$rollback = $read('deploy/bin/rollback-backend.sh');
$preflight = $read('deploy/bin/preflight-backend-server.sh');
$runbook = $read('docs/PHASE_19_PRODUCTION_DEPLOYMENT.md');
$roadmap = $read('docs/FULL_LAUNCH_ROADMAP.md');

$requireText = static function (string $name, string $content, array $fragments) use (&$failures): void {
    foreach ($fragments as $fragment) {
        if (! str_contains($content, $fragment)) {
            $failures[] = "{$name} is missing: {$fragment}";
        }
    }
};

$requireText('production env', $env, [
    'APP_ENV=production',
    'APP_DEBUG=false',
    'APP_URL=https://api.winimibakery.com',
    'SESSION_DOMAIN=.winimibakery.com',
    'SESSION_SECURE_COOKIE=true',
    'SEED_WINIMI_STAGING=false',
    'CHECKOUT_ENABLED=false',
    'PAYMENT_ENABLED=false',
    'PAYMENT_PROVIDER=disabled',
    'SMS_PROVIDER=disabled',
    'ORDER_SMS_PROVIDER=disabled',
    'OTP_EXPOSE_TEST_CODE=false',
]);
$requireText('Nginx API config', $nginx, [
    'server_name api.winimibakery.com',
    'root /var/www/winimi/backend/current/public',
    'fastcgi_pass unix:/run/php/php8.3-fpm.sock',
    'try_files $uri $uri/ /index.php?$query_string',
    'Strict-Transport-Security',
]);
$requireText('queue unit', $queue, [
    'queue:work database',
    '--tries=3',
    '--timeout=60',
    'Restart=always',
    'User=winimi',
]);
$requireText('scheduler timer', $scheduler, ['OnCalendar=*-*-* *:*:00', 'Persistent=true']);
$requireText('backup service', $backup, ['backup:run', 'backup:clean', 'UMask=0077']);
$requireText('backend deploy', $deploy, [
    'verify-backend-release.php',
    'migrate --force',
    'config:cache',
    'route:cache',
    'backend:readiness --json',
    'queue:restart',
]);
$requireText('production backend deploy', $productionDeploy, [
    'php8.3-fpm.service',
    'winimi-backend-queue.service',
    'winimi-backend-scheduler.timer',
    '/api/system/ready',
]);
$requireText('backend rollback', $rollback, [
    'BACKEND_ROLLBACK_CONFIRMED=true',
    'Database state was not changed',
    'backend:readiness --json',
]);
$requireText('backend preflight', $preflight, [
    'production PHP must be 8.3',
    'Zarinpal credential must remain empty before Phase 20',
    'nginx -t',
    'systemd-analyze verify',
]);
$requireText('Phase 19 runbook', $runbook, [
    'production_server_package=ready',
    'production_deployed=ready',
    'restore drill',
    'rollback',
]);
$requireText('roadmap', $roadmap, [
    'Phase 19A — Production deployment package',
    'production_server_package=ready',
    'Phase 19B — Live server execution',
]);

foreach (['scripts/create-backend-release.php', 'scripts/verify-backend-release.php'] as $phpFile) {
    $output = [];
    $code = 0;
    exec('php -l '.escapeshellarg($phpFile).' 2>&1', $output, $code);
    if ($code !== 0) {
        $failures[] = "PHP syntax failed for {$phpFile}: ".implode(' ', $output);
    }
}
foreach (glob('deploy/bin/*.sh') ?: [] as $shellFile) {
    $output = [];
    $code = 0;
    exec('bash -n '.escapeshellarg($shellFile).' 2>&1', $output, $code);
    if ($code !== 0) {
        $failures[] = "shell syntax failed for {$shellFile}: ".implode(' ', $output);
    }
}

$result = [
    'phase' => '19A',
    'marker' => $failures === [] ? 'production_server_package=ready' : 'blocked',
    'checkedFiles' => count($requiredFiles),
    'failures' => $failures,
];
file_put_contents('phase19-production-preparation-audit.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

if ($failures !== []) {
    fwrite(STDERR, 'Backend Phase 19 production preparation audit failed with '.count($failures)." issue(s):\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, "- {$failure}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Backend Phase 19 production deployment package audit passed.\nproduction_server_package=ready\n");
