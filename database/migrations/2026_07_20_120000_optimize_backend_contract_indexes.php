<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index(
                ['is_active', 'is_featured', 'sort_order'],
                'products_featured_contract_index',
            );
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->index(
                ['is_active', 'stock_quantity'],
                'catalog_variants_stock_contract_index',
            );
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index(
                ['customer_id', 'status', 'placed_at'],
                'orders_customer_status_placed_index',
            );
            $table->index(
                ['status', 'payment_status', 'placed_at'],
                'orders_operations_status_index',
            );
        });

        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->index(
                ['customer_id', 'status', 'created_at'],
                'payment_attempt_customer_status_index',
            );
            $table->index(
                ['status', 'expires_at'],
                'payment_attempt_expiry_status_index',
            );
        });

        Schema::table('customer_addresses', function (Blueprint $table): void {
            $table->index(
                ['customer_id', 'is_default', 'is_active'],
                'customer_addresses_default_contract_index',
            );
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->index(
                ['status', 'published_at', 'id'],
                'posts_public_contract_index',
            );
        });

        Schema::table('product_reviews', function (Blueprint $table): void {
            $table->index(
                ['customer_id', 'status', 'created_at'],
                'product_reviews_customer_status_index',
            );
        });

        Schema::table('inquiries', function (Blueprint $table): void {
            $table->index(
                ['status', 'type', 'created_at'],
                'inquiries_operations_contract_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->dropIndex('inquiries_operations_contract_index');
        });

        Schema::table('product_reviews', function (Blueprint $table): void {
            $table->dropIndex('product_reviews_customer_status_index');
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex('posts_public_contract_index');
        });

        Schema::table('customer_addresses', function (Blueprint $table): void {
            $table->dropIndex('customer_addresses_default_contract_index');
        });

        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->dropIndex('payment_attempt_customer_status_index');
            $table->dropIndex('payment_attempt_expiry_status_index');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_customer_status_placed_index');
            $table->dropIndex('orders_operations_status_index');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropIndex('catalog_variants_stock_contract_index');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_featured_contract_index');
        });
    }
};
