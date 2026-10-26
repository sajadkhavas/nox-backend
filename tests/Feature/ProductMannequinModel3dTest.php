<?php

namespace Tests\Feature;

use App\Domain\Catalog\MannequinModel3d;
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

class ProductMannequinModel3dTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_self_contained_glb_is_exposed_only_when_2d_fallback_is_usable(): void
    {
        Storage::fake((string) config('media-library.disk_name', 'public'));

        $product = $this->publishedProduct('valid-model');
        $glb = $this->glb();

        $product->addMedia(
            UploadedFile::fake()->createWithContent('product.glb', $glb),
        )->toMediaCollection(MannequinModel3d::COLLECTION);

        $this->getJson('/api/v1/products/'.$product->slug.'/mannequin-3d')->assertNotFound();

        $product->addMedia(
            UploadedFile::fake()->createWithContent('mannequin-front.png', $this->validPngContents()),
        )->toMediaCollection('mannequin-front');

        $response = $this->getJson('/api/v1/products/'.$product->slug.'/mannequin-3d')
            ->assertOk()
            ->assertJsonPath('data.format', 'glb');

        $this->assertStringContainsString(
            '/api/v1/products/'.$product->slug.'/mannequin-3d/file',
            (string) $response->json('data.url'),
        );
        $this->assertSame(strlen($glb), $response->json('data.bytes'));

        $this->get('/api/v1/products/'.$product->slug.'/mannequin-3d/file')
            ->assertOk()
            ->assertHeader('Content-Type', 'model/gltf-binary')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_external_child_resource_glb_fails_closed(): void
    {
        Storage::fake((string) config('media-library.disk_name', 'public'));

        $product = $this->publishedProduct('external-model');
        $product->addMedia(
            UploadedFile::fake()->createWithContent('mannequin-front.png', $this->validPngContents()),
        )->toMediaCollection('mannequin-front');
        $product->addMedia(
            UploadedFile::fake()->createWithContent('external.glb', $this->glb([
                'asset' => ['version' => '2.0'],
                'images' => [['uri' => 'https://example.invalid/texture.png']],
            ])),
        )->toMediaCollection(MannequinModel3d::COLLECTION);

        $product->unsetRelation('media');
        $media = $product->getFirstMedia(MannequinModel3d::COLLECTION);

        $this->assertNotNull($media);
        $this->assertFalse(MannequinModel3d::isValid($media));
        $this->getJson('/api/v1/products/'.$product->slug.'/mannequin-3d')->assertNotFound();
        $this->get('/api/v1/products/'.$product->slug.'/mannequin-3d/file')->assertNotFound();
    }

    public function test_invalid_glb_header_version_and_size_are_rejected(): void
    {
        Storage::fake((string) config('media-library.disk_name', 'public'));

        $product = $this->publishedProduct('invalid-header');
        $invalid = 'NOTG'.pack('V', 1).pack('V', 24).str_repeat("\0", 12);

        $product->addMedia(
            UploadedFile::fake()->createWithContent('invalid.glb', $invalid),
        )->toMediaCollection(MannequinModel3d::COLLECTION);

        $product->unsetRelation('media');
        $media = $product->getFirstMedia(MannequinModel3d::COLLECTION);

        $this->assertNotNull($media);
        $this->assertFalse(MannequinModel3d::isValid($media));
    }

    public function test_glb_contract_enforces_configured_size_ceiling(): void
    {
        Storage::fake((string) config('media-library.disk_name', 'public'));
        config()->set('mannequin.model3d.max_bytes', 32);

        $product = $this->publishedProduct('oversize');
        $product->addMedia(
            UploadedFile::fake()->createWithContent('oversize.glb', $this->glb()),
        )->toMediaCollection(MannequinModel3d::COLLECTION);

        $product->unsetRelation('media');
        $media = $product->getFirstMedia(MannequinModel3d::COLLECTION);

        $this->assertNotNull($media);
        $this->assertGreaterThan(32, $media->size);
        $this->assertFalse(MannequinModel3d::isValid($media));
    }

    private function publishedProduct(string $suffix): Product
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
            'mannequin_enabled' => true,
            'mannequin_slot' => 'top',
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

        return $product->fresh();
    }

    /** @param array<string, mixed>|null $document */
    private function glb(?array $document = null): string
    {
        $json = json_encode(
            $document ?? ['asset' => ['version' => '2.0'], 'scene' => 0, 'scenes' => [['nodes' => []]]],
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        $json .= str_repeat(' ', (4 - strlen($json) % 4) % 4);
        $length = 12 + 8 + strlen($json);

        return 'glTF'.pack('V', 2).pack('V', $length)
            .pack('V', strlen($json)).pack('V', 0x4E4F534A).$json;
    }

    private function validPngContents(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
    }
}
