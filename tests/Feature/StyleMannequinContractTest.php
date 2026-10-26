<?php

namespace Tests\Feature;

use App\Domain\Catalog\PublicCatalogTransformer;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StyleMannequinContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_mannequin_profile_is_fail_closed_and_uses_admin_tuning(): void
    {
        Storage::fake((string) config('media-library.disk_name', 'public'));

        $category = Category::query()->create([
            'name' => 'بالاپوش آزمایشی',
            'slug' => 'mannequin-test-tops',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'محصول مانکن آزمایشی',
            'slug' => 'mannequin-test-product',
            'product_code' => 'LBB-MANNEQUIN-TEST',
            'mannequin_enabled' => true,
            'mannequin_slot' => 'top',
        ]);

        $profile = $this->profile($product);

        $this->assertFalse($profile['enabled']);
        $this->assertNull($profile['assetUrl']);
        $this->assertSame('top', $profile['slot']);
        $this->assertSame('top-default', $profile['preset']);
        $this->assertSame(0.0, $profile['offsetX']);
        $this->assertSame(-4.0, $profile['offsetY']);
        $this->assertSame(1.0, $profile['scale']);
        $this->assertSame(30, $profile['layer']);

        $product->addMedia(
            UploadedFile::fake()->createWithContent(
                'mannequin-front.png',
                $this->validPngContents(),
            ),
        )->toMediaCollection('mannequin-front');

        $product->update([
            'mannequin_offset_x' => 12.5,
            'mannequin_offset_y' => -7.25,
            'mannequin_scale' => 1.25,
            'mannequin_layer' => 44,
        ]);

        $profile = $this->profile($product);

        $this->assertTrue($profile['enabled']);
        $this->assertNotNull($profile['assetUrl']);
        $this->assertStringContainsString('mannequin-front.png', $profile['assetUrl']);
        $this->assertSame(12.5, $profile['offsetX']);
        $this->assertSame(-7.25, $profile['offsetY']);
        $this->assertSame(1.25, $profile['scale']);
        $this->assertSame(44, $profile['layer']);

        $product->update(['mannequin_slot' => 'not-a-real-slot']);

        $profile = $this->profile($product);

        $this->assertFalse($profile['enabled']);
        $this->assertNull($profile['slot']);
        $this->assertNotNull($profile['assetUrl']);
    }

    public function test_mannequin_public_values_are_clamped_to_server_bounds(): void
    {
        Storage::fake((string) config('media-library.disk_name', 'public'));

        $category = Category::query()->create([
            'name' => 'پایین‌تنه آزمایشی',
            'slug' => 'mannequin-test-bottoms',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'محصول کران مانکن',
            'slug' => 'mannequin-boundary-product',
            'product_code' => 'LBB-MANNEQUIN-BOUNDS',
            'mannequin_enabled' => true,
            'mannequin_slot' => 'bottom',
            'mannequin_offset_x' => 999,
            'mannequin_offset_y' => -999,
            'mannequin_scale' => 9,
            'mannequin_layer' => 999,
        ]);

        $product->addMedia(
            UploadedFile::fake()->createWithContent(
                'mannequin-bounds.png',
                $this->validPngContents(),
            ),
        )->toMediaCollection('mannequin-front');

        $profile = $this->profile($product);

        $this->assertTrue($profile['enabled']);
        $this->assertSame(50.0, $profile['offsetX']);
        $this->assertSame(-50.0, $profile['offsetY']);
        $this->assertSame(2.0, $profile['scale']);
        $this->assertSame(100, $profile['layer']);
    }

    /** @return array{enabled: bool, assetUrl: ?string, slot: ?string, offsetX: float, offsetY: float, scale: float, layer: int, preset: ?string} */
    private function profile(Product $product): array
    {
        $product = $product->fresh()->load([
            'category',
            'activeVariants.color',
            'activeVariants.size',
            'evidences',
            'media',
            'mediaAssets.media',
        ]);

        return app(PublicCatalogTransformer::class)->productSummary($product)['mannequin'];
    }

    private function validPngContents(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
    }
}
