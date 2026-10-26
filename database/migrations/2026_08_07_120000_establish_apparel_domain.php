<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colors', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('code', 40)->unique();
            $table->string('hex', 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sizes', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('name', 60);
            $table->string('code', 40)->unique();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('collections', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('name', 180);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->string('publication_status', 20)->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 180)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('drops', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('name', 180);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->string('publication_status', 20)->default('draft')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 180)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('size_guides', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('unit', 12)->default('cm');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('measurement_definitions', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('code', 64)->unique();
            $table->string('label', 120);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('size_guide_measurements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('size_guide_id')->constrained('size_guides')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->foreignId('measurement_definition_id')->constrained('measurement_definitions')->restrictOnDelete();
            $table->decimal('value', 8, 2);
            $table->string('notes', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['size_guide_id', 'size_id', 'measurement_definition_id'],
                'size_guide_measurement_unique',
            );
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->string('publication_status', 20)->default('draft')->index();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('size_guide_id')->nullable()->constrained('size_guides')->restrictOnDelete();
            $table->string('publication_status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('material', 255)->nullable();
            $table->text('fabric_composition')->nullable();
            $table->string('fit', 120)->nullable();
            $table->text('care_instructions')->nullable();
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->foreignId('color_id')->nullable()->constrained('colors')->restrictOnDelete();
            $table->foreignId('size_id')->nullable()->constrained('sizes')->restrictOnDelete();
            $table->softDeletes();

            $table->unique(['product_id', 'color_id', 'size_id'], 'variant_product_color_size_unique');
            $table->index(['color_id', 'size_id', 'is_active'], 'variant_apparel_lookup_index');
        });

        Schema::create('collection_product', function (Blueprint $table): void {
            $table->foreignId('collection_id')->constrained('collections')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->primary(['collection_id', 'product_id']);
            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('drop_product', function (Blueprint $table): void {
            $table->foreignId('drop_id')->constrained('drops')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->primary(['drop_id', 'product_id']);
            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('product_media_assets', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->string('role', 40)->default('detail')->index();
            $table->string('alt_text', 255)->nullable();
            $table->string('verification_state', 20)->default('missing')->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'color_id', 'sort_order'], 'product_media_color_index');
            $table->index(['product_id', 'variant_id', 'sort_order'], 'product_media_variant_index');
        });

        Schema::create('product_evidences', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('fact_key', 64);
            $table->string('state', 20)->default('missing')->index();
            $table->text('source_reference')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'fact_key'], 'product_evidence_fact_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_evidences');
        Schema::dropIfExists('product_media_assets');
        Schema::dropIfExists('drop_product');
        Schema::dropIfExists('collection_product');

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropUnique('variant_product_color_size_unique');
            $table->dropIndex('variant_apparel_lookup_index');
            $table->dropConstrainedForeignId('color_id');
            $table->dropConstrainedForeignId('size_id');
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('size_guide_id');
            $table->dropColumn([
                'publication_status',
                'published_at',
                'material',
                'fabric_composition',
                'fit',
                'care_instructions',
            ]);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('publication_status');
        });

        Schema::dropIfExists('size_guide_measurements');
        Schema::dropIfExists('measurement_definitions');
        Schema::dropIfExists('size_guides');
        Schema::dropIfExists('drops');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('sizes');
        Schema::dropIfExists('colors');
    }
};
