<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_items', function (Blueprint $table): void {
            $table->text('image_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('gallery_items')->whereNull('image_url')->exists()) {
            throw new RuntimeException('گالری دارای ردیف بدون image_url است؛ برای بازگردانی قید، ابتدا داده‌ها را بازیابی کنید.');
        }

        Schema::table('gallery_items', function (Blueprint $table): void {
            $table->text('image_url')->nullable(false)->change();
        });
    }
};
