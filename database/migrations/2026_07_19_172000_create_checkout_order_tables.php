<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('order_number', 32)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('idempotency_key', 120);
            $table->char('request_hash', 64);
            $table->string('status', 40)->index();
            $table->string('payment_status', 40)->index();
            $table->string('delivery_method', 32)->index();
            $table->unsignedBigInteger('subtotal_toman');
            $table->unsignedBigInteger('delivery_fee_toman')->default(0);
            $table->unsignedBigInteger('packaging_fee_toman')->default(0);
            $table->unsignedBigInteger('discount_total_toman')->default(0);
            $table->unsignedBigInteger('grand_total_toman');
            $table->unsignedInteger('item_count');
            $table->unsignedSmallInteger('preparation_time_days')->default(0);
            $table->string('customer_name', 120);
            $table->string('customer_mobile', 11);
            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('reservation_expires_at')->nullable()->index();
            $table->timestamp('placed_at')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'idempotency_key'], 'orders_customer_idempotency_unique');
            $table->index(['customer_id', 'created_at'], 'orders_customer_created_index');
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->char('product_public_id', 26);
            $table->char('variant_public_id', 26);
            $table->string('product_name', 180);
            $table->string('variant_name', 120);
            $table->string('product_code', 80);
            $table->string('sku', 100);
            $table->unsignedBigInteger('unit_price_toman');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total_toman');
            $table->timestamps();

            $table->index(['order_id', 'variant_public_id'], 'order_items_order_variant_index');
        });

        Schema::create('inventory_reservations', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('status', 24)->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->string('release_reason', 80)->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'variant_id'], 'inventory_reservation_order_variant_unique');
            $table->index(['variant_id', 'status', 'expires_at'], 'inventory_reservation_availability_index');
        });

        Schema::create('order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->string('actor_type', 40)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at'], 'order_status_history_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('inventory_reservations');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
