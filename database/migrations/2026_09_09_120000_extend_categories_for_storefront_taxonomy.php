<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('public_id')
                ->constrained('categories')
                ->nullOnDelete();
            $table->string('icon_path')->nullable()->after('image_path');
            $table->boolean('show_in_header')->default(false)->after('is_active')->index();
            $table->boolean('show_on_home')->default(false)->after('show_in_header')->index();
            $table->index(['parent_id', 'sort_order'], 'categories_parent_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex('categories_parent_sort_idx');
            $table->dropIndex(['show_in_header']);
            $table->dropIndex(['show_on_home']);
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('icon_path');
        });
    }
};
