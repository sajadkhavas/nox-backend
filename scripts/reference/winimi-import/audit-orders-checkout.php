<?php

$errors = [];

$files = [
    'migration' => 'database/migrations/2026_07_19_172000_create_checkout_order_tables.php',
    'order' => 'app/Models/Order.php',
    'item' => 'app/Models/OrderItem.php',
    'reservation' => 'app/Models/InventoryReservation.php',
    'checkout' => 'app/Services/Orders/CheckoutService.php',
    'lifecycle' => 'app/Services/Orders/OrderLifecycleService.php',
    'controller' => 'app/Http/Controllers/Api/CheckoutController.php',
    'accountController' => 'app/Http/Controllers/Api/AccountOrderController.php',
    'request' => 'app/Http/Requests/CheckoutRequest.php',
    'resource' => 'app/Http/Resources/OrderResource.php',
    'routes' => 'routes/api.php',
    'schedule' => 'routes/console.php',
    'config' => 'config/winimi.php',
    'env' => '.env.example',
    'tests' => 'tests/Feature/CheckoutOrderTest.php',
];

$sources = [];
foreach ($files as $name => $path) {
    if (! is_file($path)) {
        $errors[] = "Missing required checkout file: {$path}";

        continue;
    }

    $sources[$name] = (string) file_get_contents($path);
}

$require = static function (string $source, string $needle, string $label) use (&$errors, $sources, $files): void {
    if (! isset($sources[$source]) || ! str_contains($sources[$source], $needle)) {
        $errors[] = ($files[$source] ?? $source).": missing {$label}";
    }
};

$forbid = static function (string $source, string $needle, string $label) use (&$errors, $sources, $files): void {
    if (isset($sources[$source]) && str_contains($sources[$source], $needle)) {
        $errors[] = ($files[$source] ?? $source).": contains forbidden {$label}";
    }
};

foreach (['orders', 'order_items', 'inventory_reservations', 'order_status_histories'] as $table) {
    $require('migration', "Schema::create('{$table}'", "order table {$table}");
}

foreach (['idempotency_key', 'request_hash', 'reservation_expires_at', 'subtotal_toman', 'grand_total_toman'] as $field) {
    $require('migration', $field, "order integrity field {$field}");
}

foreach (['product_name', 'variant_name', 'unit_price_toman', 'line_total_toman'] as $snapshot) {
    $require('migration', $snapshot, "immutable item snapshot {$snapshot}");
}

$require('checkout', 'lockForUpdate()', 'transactional row locking');
$require('checkout', "hash('sha256'", 'canonical request hash');
$require('checkout', "where('idempotency_key'", 'idempotency lookup');
$require('checkout', 'InventoryReservationStatus::Active', 'active inventory reservation');
$require('checkout', '$variant->current_price_toman', 'server-authoritative price');
$require('checkout', '$variant->stock_quantity - $reserved', 'reservation-aware stock calculation');
$forbid('request', 'subtotal', 'client subtotal input');
$forbid('request', 'grandTotal', 'client grand total input');
$forbid('request', 'priceToman', 'client item price input');

$require('lifecycle', 'InventoryReservationStatus::Released', 'customer cancellation release');
$require('lifecycle', 'InventoryReservationStatus::Expired', 'payment timeout release');
$require('lifecycle', 'InventoryReservationStatus::Consumed', 'verified-payment consumption boundary');
$require('lifecycle', "decrement('stock_quantity'", 'physical stock decrement after verified reservation consumption');
$require('schedule', "Schedule::command('inventory:release-expired')", 'scheduled reservation cleanup');
$require('schedule', 'withoutOverlapping()', 'non-overlapping cleanup');

$require('routes', "Route::post('checkout'", 'checkout route');
$require('routes', "Route::get('orders'", 'customer order list route');
$require('routes', "Route::post('orders/{orderId}/cancel'", 'customer order cancellation route');
$require('routes', "middleware(['auth:customer', 'customer.active'])", 'authenticated active customer boundary');
$require('accountController', '->ownedBy($request->user(\'customer\'))', 'server-side order ownership scope');

$require('config', "'orders' => [\n            'status' => 'implemented'", 'implemented order contract');
$require('config', "'payments' => [\n            'status' => 'implemented'", 'implemented payment contract');
$require('env', 'CHECKOUT_ENABLED=false', 'secure checkout default');
$require('env', 'DELIVERY_STANDARD_ENABLED=false', 'explicit delivery activation');
$require('controller', 'PaymentProviderManager $payments', 'separated payment readiness boundary');
$require('controller', "'initiationEndpoint'", 'payment initiation handoff');
$forbid('controller', 'ZARINPAL_MERCHANT_ID', 'payment credential access in checkout');

foreach ([
    'test_checkout_uses_server_prices_snapshots_and_reserves_without_decrementing_physical_stock',
    'test_same_idempotency_key_replays_one_order_and_rejects_a_different_payload',
    'test_active_reservations_prevent_overselling',
    'test_cooling_products_require_chilled_delivery_or_pickup',
    'test_customer_can_only_view_own_orders_and_cancel_unpaid_order',
    'test_expired_orders_release_reservations_and_payment_consumption_decrements_stock_once',
] as $testName) {
    $require('tests', $testName, "checkout regression test {$testName}");
}

if ($errors !== []) {
    fwrite(STDERR, "Checkout and order audit failed:\n- ".implode("\n- ", $errors)."\n");
    exit(1);
}

echo 'Checkout and order audit passed: snapshots, idempotency, ownership, delivery and reservations verified.'.PHP_EOL;
