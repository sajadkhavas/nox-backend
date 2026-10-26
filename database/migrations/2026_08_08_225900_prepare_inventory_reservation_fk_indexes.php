<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may use the legacy (order_id, variant_id) unique index as the
        // supporting index for the order_id foreign key. Create dedicated FK
        // indexes first so BE-E can safely replace the legacy uniqueness rule.
        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->index('order_id', 'inventory_reservations_order_fk_index');
            $table->index('variant_id', 'inventory_reservations_variant_fk_index');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->dropIndex('inventory_reservations_order_fk_index');
            $table->dropIndex('inventory_reservations_variant_fk_index');
        });
    }
};
