<?php

$errors = [];

$files = [
    'migration' => 'database/migrations/2026_07_20_000000_create_store_operations_tables.php',
    'orderItemMigration' => 'database/migrations/2026_07_20_001000_add_public_id_to_order_items.php',
    'address' => 'app/Models/CustomerAddress.php',
    'orderItem' => 'app/Models/OrderItem.php',
    'orderItemResource' => 'app/Http/Resources/OrderItemResource.php',
    'reviewRequest' => 'app/Http/Requests/SubmitReviewRequest.php',
    'addressController' => 'app/Http/Controllers/Api/AccountAddressController.php',
    'delivery' => 'app/Services/Store/DeliveryConfigurationService.php',
    'deliveryZone' => 'app/Models/DeliveryZone.php',
    'checkout' => 'app/Services/Orders/CheckoutService.php',
    'lifecycle' => 'app/Services/Orders/OrderLifecycleService.php',
    'content' => 'app/Http/Controllers/Api/StoreContentController.php',
    'review' => 'app/Http/Controllers/Api/ReviewController.php',
    'inquiry' => 'app/Http/Controllers/Api/InquiryController.php',
    'outbox' => 'app/Services/Notifications/NotificationOutboxService.php',
    'testingSms' => 'app/Services/Notifications/Providers/TestingSmsProvider.php',
    'kavenegarSms' => 'app/Services/Notifications/Providers/KavenegarSmsProvider.php',
    'routes' => 'routes/api.php',
    'schedule' => 'routes/console.php',
    'config' => 'config/winimi.php',
    'env' => '.env.example',
    'orderAdmin' => 'app/Filament/Resources/OrderResource/Pages/ViewOrder.php',
    'tests' => 'tests/Feature/StoreOperationsTest.php',
    'fulfillmentTests' => 'tests/Feature/OrderFulfillmentTest.php',
    'notificationTests' => 'tests/Feature/NotificationOutboxTest.php',
    'filamentTests' => 'tests/Feature/StoreOperationsFilamentTest.php',
];

$sources = [];
foreach ($files as $name => $path) {
    if (! is_file($path)) {
        $errors[] = "Missing required Phase 15 file: {$path}";

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

foreach ([
    'customer_addresses',
    'delivery_zones',
    'store_settings',
    'bakery_content_pages',
    'bakery_faqs',
    'bakery_gallery_items',
    'bakery_city_pages',
    'bakery_posts',
    'product_reviews',
    'inquiries',
    'notification_templates',
    'notification_outbox',
    'order_internal_notes',
] as $table) {
    $require('migration', "Schema::create('{$table}'", "store operations table {$table}");
}

foreach ([
    'delivery_zone_id',
    'preparation_max_days',
    'tracking_code',
    'confirmed_at',
    'preparing_at',
    'ready_at',
    'dispatched_at',
    'delivered_at',
    'restocked_at',
] as $field) {
    $require('migration', $field, "operations field {$field}");
}

$require('orderItemMigration', "char('public_id', 26)", 'public order-item identifier schema');
$require('orderItemMigration', 'Str::ulid()', 'existing order-item public-ID backfill');
$require('orderItem', '$item->public_id ??=', 'new order-item public IDs');
$require('orderItemResource', "'id' => \$this->public_id", 'public order-item ID response');
$require('reviewRequest', "'orderItemId' => ['required', 'string', 'size:26']", 'public review item validation');
$require('review', "->where('public_id', \$request->validated('orderItemId'))", 'public review item lookup');

$require('address', 'scopeOwnedBy', 'customer-owned address scope');
$require('addressController', '->ownedBy($request->user(\'customer\'))', 'server-side address ownership checks');
$require('checkout', 'resolveCustomerPayload', 'saved-address checkout resolution');
$require('checkout', "'delivery_zone_id' =>", 'delivery zone snapshot');
$require('delivery', 'StoreSetting::value', 'database operating settings');
$require('deliveryZone', 'free_delivery_threshold_toman', 'free delivery threshold');
$require('delivery', 'daily_order_limit', 'daily zone capacity');
$require('deliveryZone', 'feeFor(DeliveryMethod $method, int $subtotalToman)', 'server-authoritative zone fee');

$require('lifecycle', 'allowedTargets', 'explicit fulfillment state machine');
$require('lifecycle', 'InventoryReservationStatus::Restocked', 'one-time restock state');
$require('lifecycle', "increment('stock_quantity'", 'physical stock restoration');
$require('lifecycle', 'trackingCode', 'dispatch tracking requirement');
$require('lifecycle', 'addInternalNote', 'internal order notes');
$forbid('lifecycle', "'payment_status' => PaymentStatus::Refunded", 'false automatic refund marking');

$require('review', 'OrderStatus::Delivered', 'delivered-order review eligibility');
$require('review', '->ownedBy($request->user(\'customer\'))', 'review order ownership');
$require('review', 'ReviewStatus::Pending', 'review moderation boundary');
$require('inquiry', "hash_hmac('sha256'", 'hashed inquiry IP');
$require('inquiry', "where('created_at', '>=', now()->subMinutes(5))", 'duplicate inquiry protection');

$require('outbox', '! $this->providers->ready()', 'disabled provider pending guard');
$require('outbox', 'lockForUpdate()', 'transactional outbox locks');
$require('outbox', 'stale_processing_recovered', 'stale processing recovery');
$require('testingSms', "app()->environment('production')", 'testing provider production guard');
$require('kavenegarSms', "config('winimi.notifications.kavenegar.api_key')", 'server-side Kavenegar credential');
$forbid('kavenegarSms', 'Log::', 'credential-bearing provider logging');
$require('schedule', "Schedule::command('notifications:dispatch --limit=100')", 'outbox scheduler');
$require('schedule', 'withoutOverlapping()', 'non-overlapping scheduler');

foreach ([
    "Route::get('delivery/options'",
    "Route::get('settings'",
    "Route::get('pages/{slug}'",
    "Route::get('faqs'",
    "Route::get('gallery'",
    "Route::get('posts'",
    "Route::get('cities/{slug}'",
    "Route::post('inquiries'",
    "Route::get('addresses'",
    "Route::post('addresses'",
    "Route::post('orders/{orderId}/reviews'",
] as $route) {
    $require('routes', $route, "Phase 15 route {$route}");
}

$require('config', "'store_operations' => [", 'store operations contract');
$require('config', "'target_phase' => 15", 'Phase 15 ownership of store operations');
$require('config', "'contract_version' => '2026-07-20-phase-16'", 'current frozen contract identity');
$require('env', 'ORDER_SMS_PROVIDER=disabled', 'safe default order SMS provider');
$forbid('env', 'VITE_KAVENEGAR', 'frontend SMS secret');
$forbid('env', 'VITE_ENAMAD', 'frontend trust-code injection');

foreach ([
    'confirm',
    'prepare',
    'ready',
    'dispatch',
    'deliver',
    'cancel',
    'internalNote',
] as $action) {
    $require('orderAdmin', "Action::make('{$action}')", "controlled order action {$action}");
}

foreach ([
    'test_customer_addresses_are_owned_and_checkout_snapshots_database_delivery_rules',
    'test_store_content_is_published_separately_and_enamad_stays_hidden_until_enabled',
    'test_only_delivered_owned_items_can_receive_one_moderated_verified_review',
    'test_inquiries_use_honeypot_and_duplicate_protection',
] as $test) {
    $require('tests', $test, "store operations regression {$test}");
}

foreach ([
    'test_admin_fulfillment_transitions_are_controlled_and_queue_public_notifications',
    'test_admin_cancellation_after_payment_restocks_consumed_inventory_exactly_once',
    'test_pickup_order_skips_dispatch_and_can_be_delivered_from_ready',
] as $test) {
    $require('fulfillmentTests', $test, "fulfillment regression {$test}");
}

$require('notificationTests', 'test_disabled_provider_keeps_encrypted_notifications_pending_until_activation', 'disabled outbox regression');
$require('notificationTests', 'test_testing_provider_dispatches_from_outbox_without_external_credentials', 'testing outbox regression');
$require('filamentTests', 'test_super_admin_can_open_all_store_operations_resources_and_order_console', 'Filament operations smoke test');

if ($errors !== []) {
    fwrite(STDERR, "Store operations audit failed:\n- ".implode("\n- ", $errors)."\n");
    exit(1);
}

echo 'Store operations audit passed: addresses, delivery zones, fulfillment, content, reviews, inquiries, outbox and administration verified.'.PHP_EOL;
