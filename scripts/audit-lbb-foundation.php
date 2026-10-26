<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$required = [
    'config/lbb.php',
    'routes/api.php',
    'app/Models/Customer.php',
    'app/Models/Order.php',
    'app/Models/PaymentAttempt.php',
    'app/Services/Orders/CheckoutService.php',
];
foreach ($required as $path) {
    if (! is_file($root.'/'.$path)) {
        $errors[] = 'Missing required foundation file: '.$path;
    }
}

$forbidden = [
    'config/winimi.php',
    'app/Http/Controllers/Api/V1',
    'app/Http/Middleware/MarkLegacyApi.php',
    'deploy',
    'src',
];
foreach ($forbidden as $path) {
    if (file_exists($root.'/'.$path)) {
        $errors[] = 'Forbidden inherited path remains: '.$path;
    }
}

$activeRoots = ['app', 'bootstrap', 'config', 'database', 'routes'];
$needles = [
    'Tool'.'Master',
    'TOOL'.'MASTER',
    'tool'.'master',
    'Win'.'imi',
    'WIN'.'IMI',
    'win'.'imi',
    'Bak'.'ery',
    'BAK'.'ERY',
    'bak'.'ery',
];
foreach ($activeRoots as $activeRoot) {
    $base = $root.'/'.$activeRoot;
    if (! is_dir($base)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getFilename() === 'database.sqlite') {
            continue;
        }
        $contents = @file_get_contents($file->getPathname());
        if ($contents === false) {
            continue;
        }
        foreach ($needles as $needle) {
            if (str_contains($contents, $needle)) {
                $relative = str_replace($root.'/', '', str_replace('\\', '/', $file->getPathname()));
                $errors[] = 'Forbidden identity reference '.$needle.' in '.$relative;
                break;
            }
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, array_values(array_unique($errors))).PHP_EOL);
    exit(1);
}

echo "lbb_backend_foundation=clean\n";
