<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_quotes', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->char('request_hash', 64);
            $table->string('status', 24)->index();
            $table->string('delivery_method', 32);
            $table->foreignId('delivery_zone_id')->nullable()->constrained('delivery_zones')->nullOnDelete();
            $table->json('items_snapshot');
            $table->json('recipient_snapshot');
            $table->unsignedBigInteger('subtotal_toman');
            $table->unsignedBigInteger('delivery_fee_toman')->default(0);
            $table->unsignedBigInteger('packaging_fee_toman')->default(0);
            $table->unsignedBigInteger('discount_total_toman')->default(0);
            $table->unsignedBigInteger('grand_total_toman');
            $table->string('currency', 16)->default('TOMAN');
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->char('consumed_order_public_id', 26)->nullable()->index();
            $table->timestamps();
            $table->index(['customer_id', 'status', 'expires_at'], 'checkout_quotes_customer_status_expiry_index');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('checkout_quote_id')->nullable()->after('customer_id')->constrained('checkout_quotes')->nullOnDelete();
            $table->string('currency', 16)->default('TOMAN')->after('payment_status');
            $table->index(['checkout_quote_id', 'customer_id'], 'orders_quote_customer_index');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('color_name', 120)->nullable()->after('variant_name');
            $table->string('size_name', 120)->nullable()->after('color_name');
            $table->string('currency', 16)->default('TOMAN')->after('line_total_toman');
        });

        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->dropUnique('inventory_reservation_order_variant_unique');
            $table->string('purpose', 32)->default('checkout')->after('variant_id')->index();
            $table->string('correlation_key', 180)->nullable()->after('purpose')->unique();
            $table->index(['order_id', 'variant_id', 'status'], 'inventory_reservation_order_variant_status_index');
        });

        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('method_snapshot', 32);
            $table->string('status', 32)->index();
            $table->string('carrier', 120)->nullable();
            $table->string('tracking_reference', 180)->nullable()->index();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'updated_at'], 'shipments_status_updated_index');
        });

        Schema::create('return_requests', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('idempotency_key', 120);
            $table->char('request_hash', 64);
            $table->string('status', 32)->index();
            $table->string('resolution', 32)->nullable()->index();
            $table->text('reason');
            $table->text('admin_note')->nullable();
            $table->timestamp('requested_at')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'idempotency_key'], 'return_customer_idempotency_unique');
            $table->index(['order_id', 'status'], 'return_order_status_index');
        });

        Schema::create('return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('return_request_id')->constrained('return_requests')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('refund_value_toman');
            $table->timestamps();
            $table->unique(['return_request_id', 'order_item_id'], 'return_item_request_line_unique');
        });

        Schema::create('exchange_requests', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->foreignId('source_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('destination_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('destination_reservation_id')->nullable()->constrained('inventory_reservations')->nullOnDelete();
            $table->string('idempotency_key', 120);
            $table->char('request_hash', 64);
            $table->unsignedInteger('quantity');
            $table->string('status', 32)->index();
            $table->text('reason')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('requested_at')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'idempotency_key'], 'exchange_customer_idempotency_unique');
            $table->index(['order_id', 'status'], 'exchange_order_status_index');
            $table->index(['destination_variant_id', 'status'], 'exchange_destination_status_index');
        });

        Schema::create('refund_requests', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('return_request_id')->nullable()->constrained('return_requests')->nullOnDelete();
            $table->foreignId('payment_attempt_id')->nullable()->constrained('payment_attempts')->nullOnDelete();
            $table->string('idempotency_key', 160)->unique();
            $table->char('request_hash', 64);
            $table->string('status', 32)->index();
            $table->unsignedBigInteger('amount_toman');
            $table->string('currency', 16)->default('TOMAN');
            $table->string('provider', 32)->nullable();
            $table->string('provider_reference', 180)->nullable();
            $table->text('reason')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('requested_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status'], 'refund_order_status_index');
        });

        Schema::create('payment_callback_events', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('payment_attempt_id')->constrained('payment_attempts')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('provider', 32)->index();
            $table->string('authority', 128);
            $table->char('request_hash', 64);
            $table->string('status', 24)->index();
            $table->string('provider_reference', 180)->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'authority', 'request_hash'], 'payment_callback_replay_unique');
            $table->index(['payment_attempt_id', 'received_at'], 'payment_callback_attempt_received_index');
        });

        Schema::create('inventory_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('inventory_reservations')->nullOnDelete();
            $table->string('event_type', 40)->index();
            $table->integer('on_hand_delta')->default(0);
            $table->integer('reserved_delta')->default(0);
            $table->unsignedInteger('on_hand_after');
            $table->unsignedInteger('reserved_after');
            $table->unsignedInteger('available_after');
            $table->string('correlation_type', 40)->nullable()->index();
            $table->char('correlation_public_id', 26)->nullable()->index();
            $table->string('reason', 255)->nullable();
            $table->string('actor_type', 40)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('idempotency_key', 190)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['variant_id', 'created_at'], 'inventory_ledger_variant_created_index');
            $table->index(['order_id', 'created_at'], 'inventory_ledger_order_created_index');
        });

        Schema::create('commerce_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('event_key', 100)->index();
            $table->string('subject_type', 80);
            $table->char('subject_public_id', 26)->nullable()->index();
            $table->string('actor_type', 40)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('request_id', 120)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['subject_type', 'subject_public_id', 'created_at'], 'commerce_audit_subject_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_audit_events');
        Schema::dropIfExists('inventory_ledger_entries');
        Schema::dropIfExists('payment_callback_events');
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('exchange_requests');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('shipments');

        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->dropIndex('inventory_reservation_order_variant_status_index');
            $table->dropUnique(['correlation_key']);
            $table->dropColumn(['purpose', 'correlation_key']);
            $table->unique(['order_id', 'variant_id'], 'inventory_reservation_order_variant_unique');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['color_name', 'size_name', 'currency']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_quote_customer_index');
            $table->dropConstrainedForeignId('checkout_quote_id');
            $table->dropColumn('currency');
        });

        Schema::dropIfExists('checkout_quotes');
    }
};
