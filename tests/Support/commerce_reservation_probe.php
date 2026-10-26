<?php

use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\Commerce\InventoryLedgerService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$script, $orderId, $variantId, $correlationKey, $barrier] = $argv;
$deadline = microtime(true) + 10;
while (! file_exists($barrier) && microtime(true) < $deadline) {
    usleep(10_000);
}

try {
    $order = Order::query()->where('public_id', $orderId)->firstOrFail();
    $variant = ProductVariant::query()->where('public_id', $variantId)->firstOrFail();
    app(InventoryLedgerService::class)->reserveForOrder(
        $order, $variant, 1, now()->addMinutes(10), 'concurrency_probe', $correlationKey,
    );
    echo "reserved\n";
} catch (Throwable $exception) {
    echo get_class($exception).':'.$exception->getMessage()."\n";
}
