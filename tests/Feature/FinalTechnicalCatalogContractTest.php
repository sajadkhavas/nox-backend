<?php

namespace Tests\Feature;

use App\Domain\Catalog\PublicCatalogTransformer;
use App\Enums\EvidenceState;
use App\Enums\ProductFact;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductEvidence;
use App\Models\ProductMediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinalTechnicalCatalogContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_hierarchy_is_limited_to_three_levels_and_exposes_storefront_controls(): void
    {
        $root = Category::query()->create([
            'name' => 'شلوار',
            'slug' => 'pants-final-test',
            'is_active' => true,
            'show_in_header' => true,
            'show_on_home' => true,
            'icon_path' => 'catalog/category-icons/pants.svg',
        ]);

        $child = Category::query()->create([
            'parent_id' => $root->getKey(),
            'name' => 'جین',
            'slug' => 'jeans-final-test',
            'is_active' => true,
        ]);

        $grandchild = Category::query()->create([
            'parent_id' => $child->getKey(),
            'name' => 'بگ',
            'slug' => 'baggy-final-test',
            'is_active' => true,
        ]);

        $payload = app(PublicCatalogTransformer::class)->category($grandchild->fresh());

        $this->assertSame($child->public_id, $payload['parentPublicId']);
        $this->assertSame(2, $payload['depth']);
        $this->assertFalse($payload['showInHeader']);
        $this->assertFalse($payload['showOnHome']);

        $rootPayload = app(PublicCatalogTransformer::class)->category($root->fresh());
        $this->assertTrue($rootPayload['showInHeader']);
        $this->assertTrue($rootPayload['showOnHome']);
        $this->assertStringContainsString('/storage/catalog/category-icons/pants.svg', $rootPayload['icon']);

        $this->expectException(\DomainException::class);
        Category::query()->create([
            'parent_id' => $grandchild->getKey(),
            'name' => 'سطح چهارم',
            'slug' => 'level-four-final-test',
            'is_active' => true,
        ]);
    }

    public function test_category_hierarchy_rejects_cycles(): void
    {
        $root = Category::query()->create([
            'name' => 'ریشه',
            'slug' => 'cycle-root-final-test',
            'is_active' => true,
        ]);
        $child = Category::query()->create([
            'parent_id' => $root->getKey(),
            'name' => 'فرزند',
            'slug' => 'cycle-child-final-test',
            'is_active' => true,
        ]);

        $this->expectException(\DomainException::class);
        $root->update(['parent_id' => $child->getKey()]);
    }

    public function test_product_summary_exposes_at_most_three_verified_preview_images_in_order(): void
    {
        Storage::fake((string) config('media-library.disk_name', 'public'));

        $category = Category::query()->create([
            'name' => 'محصولات',
            'slug' => 'preview-category-final-test',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'محصول تست پیش‌نمایش',
            'slug' => 'preview-product-final-test',
            'product_code' => 'LBB-PREVIEW-FINAL',
            'is_active' => true,
        ]);

        ProductEvidence::query()->create([
            'product_id' => $product->getKey(),
            'fact_key' => ProductFact::Media->value,
            'state' => EvidenceState::Verified,
            'source_reference' => 'final-technical-test',
        ]);

        foreach ([30, 10, 20, 40] as $index => $sortOrder) {
            $asset = ProductMediaAsset::query()->create([
                'product_id' => $product->getKey(),
                'role' => 'gallery',
                'alt_text' => 'preview '.($index + 1),
                'verification_state' => EvidenceState::Verified,
                'sort_order' => $sortOrder,
            ]);
            $asset->addMedia(UploadedFile::fake()->image('preview-'.($index + 1).'.png', 32, 32))
                ->toMediaCollection('asset');
        }

        $product = $product->fresh()->load([
            'category',
            'activeVariants.color',
            'activeVariants.size',
            'evidences',
            'media',
            'mediaAssets.media',
        ]);

        $summary = app(PublicCatalogTransformer::class)->productSummary($product);

        $this->assertCount(3, $summary['previewImages']);
        $this->assertSame($summary['previewImages'][0], $summary['primaryImage']);
        $this->assertStringContainsString('preview-2.png', $summary['previewImages'][0]);
        $this->assertStringContainsString('preview-3.png', $summary['previewImages'][1]);
        $this->assertStringContainsString('preview-1.png', $summary['previewImages'][2]);
    }
}
