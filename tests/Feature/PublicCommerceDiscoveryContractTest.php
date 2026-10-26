<?php

namespace Tests\Feature;

use App\Enums\EvidenceState;
use App\Enums\ProductFact;
use App\Enums\PublicationStatus;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductEvidence;
use App\Models\ProductMediaAsset;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicCommerceDiscoveryContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_color_and_size_discovery_only_exposes_dimensions_used_by_public_catalog(): void
    {
        $fixture = $this->publishedProduct('discovery');

        Color::create([
            'name' => 'Unused color',
            'slug' => 'unused-color',
            'code' => 'UNUSED-COLOR',
            'is_active' => true,
        ]);

        Size::create([
            'name' => 'Unused size',
            'code' => 'UNUSED-SIZE',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/colors')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.publicId', $fixture['color']->public_id)
            ->assertJsonPath('data.0.slug', $fixture['color']->slug)
            ->assertJsonMissing(['id' => $fixture['color']->id])
            ->assertJsonMissing(['code' => 'UNUSED-COLOR']);

        $this->getJson('/api/v1/sizes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.publicId', $fixture['size']->public_id)
            ->assertJsonPath('data.0.code', $fixture['size']->code)
            ->assertJsonMissing(['id' => $fixture['size']->id])
            ->assertJsonMissing(['code' => 'UNUSED-SIZE']);
    }

    public function test_media_contract_preserves_verified_color_and_variant_associations_without_storage_paths(): void
    {
        Storage::fake((string) config('media-library.disk_name', 'public'));

        $fixture = $this->publishedProduct('media-association');
        $asset = ProductMediaAsset::query()
            ->where('product_id', $fixture['product']->id)
            ->firstOrFail();

        $imageBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WlX6gAAAABJRU5ErkJggg==',
            true,
        );
        $this->assertIsString($imageBytes);

        $asset->addMedia(UploadedFile::fake()->createWithContent('front.png', $imageBytes))
            ->withCustomProperties([
                'width' => 800,
                'height' => 1000,
            ])
            ->toMediaCollection('asset');

        $response = $this->getJson('/api/v1/products/'.$fixture['product']->slug)
            ->assertOk()
            ->assertJsonPath('data.media.0.publicId', $asset->public_id)
            ->assertJsonPath('data.media.0.role', 'primary')
            ->assertJsonPath('data.media.0.width', 800)
            ->assertJsonPath('data.media.0.height', 1000)
            ->assertJsonPath('data.media.0.colorPublicId', $fixture['color']->public_id)
            ->assertJsonPath('data.media.0.variantPublicId', $fixture['variant']->public_id)
            ->assertJsonPath('data.variants.0.mediaPublicIds.0', $asset->public_id);

        $media = $response->json('data.media.0');

        $this->assertArrayHasKey('url', $media);
        $this->assertArrayNotHasKey('disk', $media);
        $this->assertArrayNotHasKey('file_name', $media);
        $this->assertArrayNotHasKey('collection_name', $media);
    }

    private function publishedProduct(string $suffix): array
    {
        $category = Category::create([
            'name' => 'Category '.$suffix,
            'slug' => 'category-'.$suffix,
            'publication_status' => PublicationStatus::Published,
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Product '.$suffix,
            'slug' => 'product-'.$suffix,
            'product_code' => 'P-'.strtoupper($suffix),
            'publication_status' => PublicationStatus::Draft,
            'is_active' => true,
        ]);

        $color = Color::create([
            'name' => 'Color '.$suffix,
            'slug' => 'color-'.$suffix,
            'code' => 'C-'.strtoupper($suffix),
            'is_active' => true,
        ]);

        $size = Size::create([
            'name' => 'Size '.$suffix,
            'code' => 'S-'.strtoupper($suffix),
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'size_id' => $size->id,
            'name' => 'Default',
            'sku' => 'SKU-'.strtoupper($suffix),
            'regular_price_toman' => 100000,
            'stock_quantity' => 10,
            'low_stock_threshold' => 3,
            'is_default' => true,
            'is_active' => true,
        ]);

        ProductMediaAsset::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'variant_id' => $variant->id,
            'role' => 'primary',
            'alt_text' => 'Product '.$suffix,
            'verification_state' => EvidenceState::Verified,
        ]);

        foreach ([
            ProductFact::Name,
            ProductFact::Media,
            ProductFact::Price,
            ProductFact::Colors,
            ProductFact::Sizes,
            ProductFact::Stock,
            ProductFact::Sku,
        ] as $fact) {
            ProductEvidence::create([
                'product_id' => $product->id,
                'fact_key' => $fact->value,
                'state' => EvidenceState::Verified,
                'source_reference' => 'internal://'.$suffix.'/'.$fact->value,
            ]);
        }

        $product->update(['publication_status' => PublicationStatus::Published]);

        return compact('category', 'product', 'color', 'size', 'variant');
    }
}
