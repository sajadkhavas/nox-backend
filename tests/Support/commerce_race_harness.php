<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryLedgerEntry;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('database.default') !== 'mysql') {
    fwrite(STDERR, "Race harness requires MySQL.\n");
    exit(2);
}

Artisan::call('migrate:fresh', ['--force' => true]);

$customer = Customer::query()->create([
    'mobile' => '09123334444',
    'mobile_verified_at' => now(),
    'is_active' => true,
]);
$category = Category::query()->create([
    'name' => 'Race Harness',
    'slug' => 'race-harness',
    'is_active' => true,
]);
$product = Product::query()->create([
    'category_id' => $category->id,
    'name' => 'Race Harness Product',
    'slug' => 'race-harness-product',
    'product_code' => 'RACE-HARNESS',
    'is_active' => true,
]);
$variant = ProductVariant::query()->create([
    'product_id' => $product->id,
    'name' => 'Last Unit',
    'sku' => 'RACE-HARNESS-1',
    'regular_price_toman' => 1000,
    'stock_quantity' => 1,
    'low_stock_threshold' => 0,
    'is_default' => true,
    'is_active' => true,
]);
$order = Order::query()->create([
    'customer_id' => $customer->id,
    'order_number' => 'LBB-RACE-HARNESS',
    'idempotency_key' => 'race-harness-order-key',
    'request_hash' => hash('sha256', 'race-harness'),
    'status' => OrderStatus::AwaitingPayment,
    'payment_status' => PaymentStatus::Unpaid,
    'delivery_method' => 'pickup',
    'subtotal_toman' => 1000,
    'delivery_fee_toman' => 0,
    'packaging_fee_toman' => 0,
    'discount_total_toman' => 0,
    'grand_total_toman' => 1000,
    'currency' => 'TOMAN',
    'item_count' => 1,
    'preparation_time_days' => 0,
    'preparation_max_days' => 0,
    'customer_name' => 'Race',
    'customer_mobile' => '09123334444',
    'reservation_expires_at' => now()->addMinutes(20),
    'placed_at' => now(),
]);

$barrier = storage_path('framework/testing/commerce-race-'.uniqid('', true));
@unlink($barrier);
$probe = base_path('tests/Support/commerce_reservation_probe.php');
$commands = [
    [PHP_BINARY, $probe, $order->public_id, $variant->public_id, 'mysql-race-a', $barrier],
    [PHP_BINARY, $probe, $order->public_id, $variant->public_id, 'mysql-race-b', $barrier],
];
$processes = [];
foreach ($commands as $command) {
    $spec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open($command, $spec, $pipes, base_path());
    if (! is_resource($process)) {
        fwrite(STDERR, "Could not start race probe.\n");
        exit(3);
    }
    fclose($pipes[0]);
    $processes[] = [$process, $pipes];
}
file_put_contents($barrier, 'go');
$outputs = [];
foreach ($processes as [$process, $pipes]) {
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    if ($exit !== 0) {
        fwrite(STDERR, $stderr."\n");
        exit(4);
    }
    $outputs[] = trim($stdout);
}
@unlink($barrier);

$reserved = count(array_filter($outputs, fn (string $value): bool => $value === 'reserved'));
$rejected = count(array_filter($outputs, fn (string $value): bool => str_contains($value, 'موجودی کافی')));
$reservationCount = InventoryReservation::query()->count();
$ledgerCount = InventoryLedgerEntry::query()->count();

echo 'race_outputs='.json_encode($outputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
echo "reserved={$reserved} rejected={$rejected} reservations={$reservationCount} ledger={$ledgerCount}".PHP_EOL;

if ($reserved !== 1 || $rejected !== 1 || $reservationCount !== 1 || $ledgerCount !== 2) {
    fwrite(STDERR, "Oversell race acceptance failed.\n");
    exit(1);
}

echo "oversell_race=PASS\n";
