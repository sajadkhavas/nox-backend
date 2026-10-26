<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('mannequin_enabled')->default(false);
            $table->string('mannequin_slot', 32)->nullable();
            $table->string('mannequin_preset', 64)->nullable();
            $table->decimal('mannequin_offset_x', 8, 4)->nullable();
            $table->decimal('mannequin_offset_y', 8, 4)->nullable();
            $table->decimal('mannequin_scale', 8, 4)->nullable();
            $table->smallInteger('mannequin_layer')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'mannequin_enabled',
                'mannequin_slot',
                'mannequin_preset',
                'mannequin_offset_x',
                'mannequin_offset_y',
                'mannequin_scale',
                'mannequin_layer',
            ]);
        });
    }
};
