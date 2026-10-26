<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->change();
            $table->char('guest_token_hash', 64)->nullable()->index();
            $table->boolean('marketing_enabled')->default(false);
            $table->json('preferences')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('push_subscriptions')->whereNull('customer_id')->exists()) {
            throw new RuntimeException('ابتدا اشتراک‌های مهمان را به‌صورت امن منتقل یا حذف کنید؛ بازگردانی ستون اجباری بدون آن ممکن نیست.');
        }

        Schema::table('push_subscriptions', function (Blueprint $table): void {
            $table->dropColumn(['guest_token_hash', 'marketing_enabled', 'preferences']);
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};
