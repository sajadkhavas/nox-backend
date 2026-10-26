<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('title', 80)->default('آدرس من');
            $table->string('recipient_name', 120);
            $table->string('mobile', 11);
            $table->string('province', 100);
            $table->string('city', 100);
            $table->text('address_line');
            $table->string('postal_code', 20)->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['customer_id', 'is_active'], 'customer_addresses_owner_active_index');
        });

        Schema::create('delivery_zones', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('name', 140);
            $table->string('province', 100)->nullable()->index();
            $table->string('city', 100)->nullable()->index();
            $table->boolean('standard_enabled')->default(false);
            $table->boolean('pickup_enabled')->default(false);
            $table->unsignedBigInteger('standard_fee_toman')->default(0);
            $table->unsignedBigInteger('pickup_fee_toman')->default(0);
            $table->unsignedBigInteger('packaging_fee_toman')->default(0);
            $table->unsignedBigInteger('minimum_order_toman')->nullable();
            $table->unsignedBigInteger('free_delivery_threshold_toman')->nullable();
            $table->unsignedSmallInteger('preparation_min_days')->default(0);
            $table->unsignedSmallInteger('preparation_max_days')->default(0);
            $table->unsignedSmallInteger('daily_order_limit')->nullable();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['is_active', 'province', 'city', 'priority'], 'delivery_zones_resolution_index');
        });

        Schema::create('store_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 80)->default('general')->index();
            $table->string('key', 140)->unique();
            $table->string('type', 32)->default('string');
            $table->text('value')->nullable();
            $table->string('label', 180);
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('content_pages', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('type', 40)->default('page')->index();
            $table->string('slug', 160)->unique();
            $table->string('title', 220);
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('meta_title', 220)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 100)->default('general')->index();
            $table->string('question', 500);
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('gallery_items', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 220);
            $table->text('caption')->nullable();
            $table->text('image_url');
            $table->text('link_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('slug', 180)->unique();
            $table->string('title', 260);
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('category', 120)->nullable()->index();
            $table->json('tags')->nullable();
            $table->text('cover_url')->nullable();
            $table->string('author', 160)->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamps();
        });

        Schema::create('product_reviews', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 180)->nullable();
            $table->text('body')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->text('moderation_note')->nullable();
            $table->boolean('is_verified_purchase')->default(true)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['customer_id', 'order_item_id'], 'product_reviews_customer_item_unique');
            $table->index(['product_id', 'status', 'published_at'], 'product_reviews_public_index');
        });

        Schema::create('inquiries', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('type', 32)->index();
            $table->string('full_name', 120);
            $table->string('mobile', 11)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('subject', 220)->nullable();
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->string('status', 32)->default('new')->index();
            $table->char('ip_hash', 64)->nullable()->index();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('channel', 32)->default('sms')->index();
            $table->string('provider_template', 120)->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('notification_outbox', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('channel', 32)->default('sms')->index();
            $table->text('destination');
            $table->string('template_key', 120)->index();
            $table->json('payload')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->string('provider', 40)->default('disabled')->index();
            $table->string('provider_message_id', 180)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'available_at', 'id'], 'notification_outbox_dispatch_index');
        });

        Schema::create('order_internal_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id', 'created_at'], 'order_internal_notes_order_index');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('delivery_zone_id')->nullable()->after('delivery_method')->constrained('delivery_zones')->nullOnDelete();
            $table->unsignedSmallInteger('preparation_max_days')->default(0)->after('preparation_time_days');
            $table->string('tracking_code', 160)->nullable()->after('notes');
            $table->timestamp('confirmed_at')->nullable()->after('paid_at');
            $table->timestamp('preparing_at')->nullable()->after('confirmed_at');
            $table->timestamp('ready_at')->nullable()->after('preparing_at');
            $table->timestamp('dispatched_at')->nullable()->after('ready_at');
            $table->timestamp('delivered_at')->nullable()->after('dispatched_at');
            $table->timestamp('admin_cancelled_at')->nullable()->after('cancelled_at');
        });

        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->timestamp('restocked_at')->nullable()->after('consumed_at');
        });

        DB::table('store_settings')->insert([
            ['group' => 'orders', 'key' => 'orders.accepting_orders', 'type' => 'boolean', 'value' => '1', 'label' => 'پذیرش سفارش جدید', 'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'orders', 'key' => 'orders.minimum_total_toman', 'type' => 'integer', 'value' => '0', 'label' => 'حداقل مبلغ سفارش', 'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'contact', 'key' => 'contact.phone', 'type' => 'string', 'value' => '', 'label' => 'شماره تماس', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'contact', 'key' => 'contact.email', 'type' => 'string', 'value' => '', 'label' => 'ایمیل', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'contact', 'key' => 'contact.address', 'type' => 'string', 'value' => '', 'label' => 'آدرس', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'social', 'key' => 'social.instagram', 'type' => 'string', 'value' => '', 'label' => 'اینستاگرام', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'trust', 'key' => 'trust.enamad_enabled', 'type' => 'boolean', 'value' => '0', 'label' => 'فعال‌بودن اینماد', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'trust', 'key' => 'trust.enamad_badge_code', 'type' => 'string', 'value' => '', 'label' => 'کد نشان اینماد', 'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('notification_templates')->insert([
            ['key' => 'order.paid', 'channel' => 'sms', 'body' => 'سفارش {{order_number}} با موفقیت پرداخت شد.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'order.preparing', 'channel' => 'sms', 'body' => 'پردازش سفارش {{order_number}} آغاز شد.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'order.ready', 'channel' => 'sms', 'body' => 'سفارش {{order_number}} آماده تحویل یا ارسال است.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'order.dispatched', 'channel' => 'sms', 'body' => 'سفارش {{order_number}} ارسال شد. کد پیگیری: {{tracking_code}}', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'order.delivered', 'channel' => 'sms', 'body' => 'سفارش {{order_number}} تحویل شد. از خرید شما سپاسگزاریم.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'order.cancelled', 'channel' => 'sms', 'body' => 'سفارش {{order_number}} لغو شد.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->dropColumn('restocked_at');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('delivery_zone_id');
            $table->dropColumn([
                'preparation_max_days',
                'tracking_code',
                'confirmed_at',
                'preparing_at',
                'ready_at',
                'dispatched_at',
                'delivered_at',
                'admin_cancelled_at',
            ]);
        });

        Schema::dropIfExists('order_internal_notes');
        Schema::dropIfExists('notification_outbox');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('content_pages');
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('customer_addresses');
    }
};
