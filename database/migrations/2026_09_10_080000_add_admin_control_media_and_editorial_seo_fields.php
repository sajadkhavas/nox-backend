<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gallery_items') && ! Schema::hasColumn('gallery_items', 'image_path')) {
            Schema::table('gallery_items', function (Blueprint $table): void {
                $table->string('image_path', 500)->nullable();
            });
        }

        if (Schema::hasTable('posts')) {
            $needsCover = ! Schema::hasColumn('posts', 'cover_image_path');
            $needsMetaTitle = ! Schema::hasColumn('posts', 'meta_title');
            $needsMetaDescription = ! Schema::hasColumn('posts', 'meta_description');

            if ($needsCover || $needsMetaTitle || $needsMetaDescription) {
                Schema::table('posts', function (Blueprint $table) use ($needsCover, $needsMetaTitle, $needsMetaDescription): void {
                    if ($needsCover) {
                        $table->string('cover_image_path', 500)->nullable();
                    }
                    if ($needsMetaTitle) {
                        $table->string('meta_title', 220)->nullable();
                    }
                    if ($needsMetaDescription) {
                        $table->text('meta_description')->nullable();
                    }
                });
            }
        }

        if (Schema::hasTable('collections') && ! Schema::hasColumn('collections', 'cover_image_path')) {
            Schema::table('collections', function (Blueprint $table): void {
                $table->string('cover_image_path', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gallery_items') && Schema::hasColumn('gallery_items', 'image_path')) {
            Schema::table('gallery_items', function (Blueprint $table): void {
                $table->dropColumn('image_path');
            });
        }

        if (Schema::hasTable('posts')) {
            $columns = collect(['cover_image_path', 'meta_title', 'meta_description'])
                ->filter(fn (string $column): bool => Schema::hasColumn('posts', $column))
                ->values()
                ->all();

            if ($columns !== []) {
                Schema::table('posts', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('collections') && Schema::hasColumn('collections', 'cover_image_path')) {
            Schema::table('collections', function (Blueprint $table): void {
                $table->dropColumn('cover_image_path');
            });
        }
    }
};
