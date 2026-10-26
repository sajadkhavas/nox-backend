<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table): void {
            $table->boolean('immediate_courier_enabled')->default(false)->after('pickup_fee_toman');
            $table->unsignedBigInteger('immediate_courier_fee_toman')->default(0)->after('immediate_courier_enabled');
            $table->boolean('tipax_enabled')->default(false)->after('immediate_courier_fee_toman');
            $table->unsignedBigInteger('tipax_fee_toman')->default(0)->after('tipax_enabled');
            $table->boolean('decapost_enabled')->default(false)->after('tipax_fee_toman');
            $table->unsignedBigInteger('decapost_fee_toman')->default(0)->after('decapost_enabled');
            $table->boolean('express_post_enabled')->default(false)->after('decapost_fee_toman');
            $table->unsignedBigInteger('express_post_fee_toman')->default(0)->after('express_post_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table): void {
            $table->dropColumn([
                'immediate_courier_enabled',
                'immediate_courier_fee_toman',
                'tipax_enabled',
                'tipax_fee_toman',
                'decapost_enabled',
                'decapost_fee_toman',
                'express_post_enabled',
                'express_post_fee_toman',
            ]);
        });
    }
};
