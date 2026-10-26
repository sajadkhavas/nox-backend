<?php

$errors = [];

$requiredFiles = [
    'app/Models/BakeryCategory.php',
    'app/Models/BakeryProduct.php',
    'app/Models/BakeryProductVariant.php',
    'app/Http/Controllers/Api/CatalogController.php',
    'app/Http/Resources/BakeryCategoryResource.php',
    'app/Http/Resources/BakeryProductResource.php',
    'app/Http/Resources/BakeryVariantResource.php',
    'app/Filament/Resources/BakeryCategoryResource.php',
    'app/Filament/Resources/BakeryProductResource.php',
    'database/migrations/2026_07_19_120000_create_bakery_catalog_tables.php',
    'tests/Feature/BakeryCatalogApiTest.php',
];

foreach ($requiredFiles as $file) {
    if (! is_file($file)) {
        $errors[] = "Missing required Phase 11 file: {$file}";
    }
}

$read = static fn (string $path): string => is_file($path)
    ? (string) file_get_contents($path)
    : '';

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

$requireText('routes/api.php', "Route::prefix('catalog')", 'public catalog route group');
$requireText('config/winimi.php', "'catalog' => [", 'catalog contract');
$requireText('config/winimi.php', "'status' => 'implemented'", 'implemented contract status');
$requireText('app/Models/BakeryProduct.php', "'content_verified'", 'content verification boundary');
$requireText('app/Models/BakeryProduct.php', "'media_verified'", 'media verification boundary');
$requireText('app/Models/BakeryProductVariant.php', 'regular_price_toman', 'server-side variant pricing');
$requireText('app/Models/BakeryProductVariant.php', 'stock_quantity', 'variant inventory');
$requireText('app/Http/Resources/BakeryProductResource.php', "'inventoryVerified' => true", 'authoritative inventory marker');
$requireText('app/Http/Resources/BakeryProductResource.php', "'contentVerified'", 'content verification response');
$requireText('app/Http/Resources/BakeryProductResource.php', "'mediaVerified'", 'media verification response');
$requireText('app/Http/Controllers/Api/CatalogController.php', "'price-asc'", 'server-side price sorting');
$requireText('app/Filament/Resources/BakeryProductResource.php', "Repeater::make('variants')", 'variant management form');

foreach ([
    'app/Models/BakeryCategory.php',
    'app/Models/BakeryProduct.php',
    'app/Models/BakeryProductVariant.php',
    'app/Http/Controllers/Api/CatalogController.php',
    'app/Http/Resources/BakeryProductResource.php',
    'app/Filament/Resources/BakeryCategoryResource.php',
    'app/Filament/Resources/BakeryProductResource.php',
] as $file) {
    $forbidText($file, 'ToolMaster', 'legacy ToolMaster domain reference');
    $forbidText($file, 'rfq', 'legacy RFQ field or workflow');
    $forbidText($file, 'price_range', 'legacy industrial price range');
}

if ($errors !== []) {
    fwrite(STDERR, 'Bakery catalog audit failed with '.count($errors)." issue(s):\n");
    foreach ($errors as $error) {
        fwrite(STDERR, "- {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, 'Bakery catalog audit passed: Phase 11 domain, API and Filament contracts verified.'.PHP_EOL);
