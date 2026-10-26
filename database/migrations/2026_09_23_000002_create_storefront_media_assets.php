<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_media_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 220);
            $table->string('alt_text', 500)->nullable();
            $table->string('usage', 40)->default('unassigned')->index();
            $table->string('status', 40)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_media_assets');
    }
};
