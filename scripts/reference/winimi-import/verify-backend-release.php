<?php

declare(strict_types=1);

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

$releaseDir = realpath($argv[1] ?? '');
if ($releaseDir === false || ! is_dir($releaseDir)) {
    throw new RuntimeException('Usage: php scripts/verify-backend-release.php <release-directory>');
}

$manifestPath = $releaseDir.DIRECTORY_SEPARATOR.'release-manifest.json';
$appDir = $releaseDir.DIRECTORY_SEPARATOR.'app';
if (! is_file($manifestPath) || ! is_dir($appDir)) {
    throw new RuntimeException('Backend release is incomplete.');
}

$manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
if (($manifest['format'] ?? null) !== 'winimi-backend-release-v1') {
    throw new RuntimeException('Unsupported backend release manifest format.');
}
if (! preg_match('/^[a-f0-9]{20}$/', (string) ($manifest['releaseId'] ?? ''))) {
    throw new RuntimeException('Backend release ID is invalid.');
}
if (($manifest['contractVersion'] ?? null) !== '2026-07-20-phase-16') {
    throw new RuntimeException('Backend contract version drift detected.');
}

$actual = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($appDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY,
);
foreach ($iterator as $file) {
    if ($file->isLink()) {
        throw new RuntimeException('Symlink is forbidden in immutable backend release.');
    }
    if (! $file->isFile()) {
        continue;
    }
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($appDir) + 1));
    $actual[$relative] = [
        'bytes' => $file->getSize(),
        'sha256' => hash_file('sha256', $file->getPathname()),
    ];
}
ksort($actual, SORT_STRING);

$expected = [];
foreach (($manifest['files'] ?? []) as $file) {
    $path = $file['path'] ?? null;
    if (! is_string($path) || $path === '' || isset($expected[$path])) {
        throw new RuntimeException('Backend release manifest contains an invalid or duplicate path.');
    }
    $expected[$path] = [
        'bytes' => $file['bytes'] ?? null,
        'sha256' => $file['sha256'] ?? null,
    ];
}
ksort($expected, SORT_STRING);

if (array_keys($actual) !== array_keys($expected)) {
    throw new RuntimeException('Backend release file list differs from the manifest.');
}

$digest = hash_init('sha256');
$totalBytes = 0;
foreach ($expected as $path => $file) {
    if ($actual[$path] !== $file) {
        throw new RuntimeException("Backend release checksum or size mismatch: {$path}");
    }
    hash_update($digest, $path."\0".$file['sha256']."\0");
    $totalBytes += (int) $file['bytes'];
}
$releaseId = substr(hash_final($digest), 0, 20);
if ($releaseId !== $manifest['releaseId']) {
    throw new RuntimeException('Backend release content digest mismatch.');
}
if (count($expected) !== ($manifest['fileCount'] ?? null) || $totalBytes !== ($manifest['totalBytes'] ?? null)) {
    throw new RuntimeException('Backend release manifest totals are invalid.');
}

foreach (['artisan', 'bootstrap/app.php', 'composer.lock', 'public/index.php', 'vendor/autoload.php'] as $required) {
    if (! isset($actual[$required])) {
        throw new RuntimeException("Required backend release file is missing: {$required}");
    }
}

fwrite(STDOUT, "Verified backend release {$releaseId} (".count($expected)." files).\n");
