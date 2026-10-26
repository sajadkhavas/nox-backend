<?php

declare(strict_types=1);

$sourceRoot = realpath($argv[1] ?? getcwd());
$releaseRoot = $argv[2] ?? '.release';

if ($sourceRoot === false || ! is_dir($sourceRoot)) {
    throw new RuntimeException('Backend source directory is missing.');
}

$releaseRoot = str_starts_with($releaseRoot, DIRECTORY_SEPARATOR)
    ? $releaseRoot
    : getcwd().DIRECTORY_SEPARATOR.$releaseRoot;

$required = [
    'artisan',
    'bootstrap/app.php',
    'composer.lock',
    'public/index.php',
    'vendor/autoload.php',
];
foreach ($required as $path) {
    if (! is_file($sourceRoot.DIRECTORY_SEPARATOR.$path)) {
        throw new RuntimeException("Required backend release file is missing: {$path}");
    }
}

$excludedPrefixes = [
    '.git/', '.github/', '.release/', '.phase19-release/', '.idea/', '.vscode/',
    'deploy/', 'docs/', 'scripts/', 'tests/', 'node_modules/',
    'storage/app/', 'storage/framework/', 'storage/logs/',
    'bootstrap/cache/',
];
$excludedExact = [
    '.env', '.env.example', '.phpunit.result.cache', 'phpunit.xml',
    'database/database.sqlite', 'public/storage',
    'backend-release-output.json', 'phase19-production-preparation-audit.json',
];
$forbiddenPatterns = [
    '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
    '/\b(?:APP_KEY|DB_PASSWORD|ZARINPAL_MERCHANT_ID|KAVENEGAR_API_KEY|ENAMAD_BADGE_CODE)\s*=/',
    '/\b(?:mysql|postgres(?:ql)?):\/\/[^\s"\']+:[^\s"\']+@/i',
];

$isExcluded = static function (string $relative) use ($excludedPrefixes, $excludedExact): bool {
    $relative = str_replace('\\', '/', $relative);
    if (! str_contains($relative, '/') && str_ends_with(strtolower($relative), '.md')) {
        return true;
    }
    if (in_array($relative, $excludedExact, true)) {
        return true;
    }
    foreach ($excludedPrefixes as $prefix) {
        if (str_starts_with($relative, $prefix)) {
            return true;
        }
    }
    return false;
};

$files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY,
);
foreach ($iterator as $file) {
    if ($file->isLink()) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($sourceRoot) + 1));
        if (! $isExcluded($relative)) {
            throw new RuntimeException("Symlink is forbidden in backend release: {$relative}");
        }
        continue;
    }
    if (! $file->isFile()) {
        continue;
    }
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($sourceRoot) + 1));
    if ($isExcluded($relative)) {
        continue;
    }
    $bytes = file_get_contents($file->getPathname());
    if ($bytes === false) {
        throw new RuntimeException("Unable to read backend release file: {$relative}");
    }
    if (! str_starts_with($relative, 'vendor/')) {
        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $bytes) === 1) {
                throw new RuntimeException("Secret-shaped content found in backend release file: {$relative}");
            }
        }
    }
    $files[$relative] = [
        'source' => $file->getPathname(),
        'bytes' => strlen($bytes),
        'sha256' => hash('sha256', $bytes),
    ];
}
ksort($files, SORT_STRING);

if ($files === []) {
    throw new RuntimeException('Backend release file set is empty.');
}

$digest = hash_init('sha256');
foreach ($files as $relative => $file) {
    hash_update($digest, $relative."\0".$file['sha256']."\0");
}
$releaseId = substr(hash_final($digest), 0, 20);
$releaseDir = rtrim($releaseRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'backend-'.$releaseId;
$tempDir = rtrim($releaseRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.backend-staging-'.$releaseId.'-'.getmypid();

$removeTree = static function (string $directory) use (&$removeTree): void {
    if (! file_exists($directory)) {
        return;
    }
    if (is_link($directory) || is_file($directory)) {
        unlink($directory);
        return;
    }
    foreach (new FilesystemIterator($directory) as $entry) {
        $removeTree($entry->getPathname());
    }
    rmdir($directory);
};

if (! is_dir($releaseRoot) && ! mkdir($releaseRoot, 0755, true) && ! is_dir($releaseRoot)) {
    throw new RuntimeException('Unable to create backend release root.');
}
$removeTree($tempDir);
if (! mkdir($tempDir.DIRECTORY_SEPARATOR.'app', 0755, true)) {
    throw new RuntimeException('Unable to create backend release staging directory.');
}

foreach ($files as $relative => $file) {
    $destination = $tempDir.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $parent = dirname($destination);
    if (! is_dir($parent) && ! mkdir($parent, 0755, true) && ! is_dir($parent)) {
        throw new RuntimeException("Unable to create backend release directory: {$parent}");
    }
    if (! copy($file['source'], $destination)) {
        throw new RuntimeException("Unable to copy backend release file: {$relative}");
    }
    chmod($destination, $relative === 'artisan' ? 0755 : 0644);
}

$manifestFiles = [];
$totalBytes = 0;
foreach ($files as $relative => $file) {
    $manifestFiles[] = [
        'path' => $relative,
        'bytes' => $file['bytes'],
        'sha256' => $file['sha256'],
    ];
    $totalBytes += $file['bytes'];
}

$manifest = [
    'format' => 'winimi-backend-release-v1',
    'releaseId' => $releaseId,
    'contractVersion' => '2026-07-20-phase-16',
    'runtime' => [
        'phpMinimum' => '8.2',
        'entrypoint' => 'app/public/index.php',
        'artisan' => 'app/artisan',
        'readinessPath' => '/api/system/ready',
    ],
    'fileCount' => count($manifestFiles),
    'totalBytes' => $totalBytes,
    'files' => $manifestFiles,
];
file_put_contents(
    $tempDir.DIRECTORY_SEPARATOR.'release-manifest.json',
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
);

if (is_dir($releaseDir)) {
    $removeTree($tempDir);
} elseif (! rename($tempDir, $releaseDir)) {
    $removeTree($tempDir);
    throw new RuntimeException('Unable to finalize backend release directory.');
}

file_put_contents(
    getcwd().DIRECTORY_SEPARATOR.'backend-release-output.json',
    json_encode(['releaseId' => $releaseId, 'releaseDir' => $releaseDir], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
);

fwrite(STDOUT, "Created deterministic backend release {$releaseId} at {$releaseDir}.\n");
