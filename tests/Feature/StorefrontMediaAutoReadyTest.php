<?php

namespace Tests\Feature;

use App\Models\StorefrontMediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontMediaAutoReadyTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_upload_attaches_original_and_approves_optimized_image(): void
    {
        Storage::fake('public');

        $asset = StorefrontMediaAsset::createFromUpload(
            UploadedFile::fake()->image('store.jpg', 1200, 900),
            ['title' => 'فروشگاه', 'usage' => 'hero'],
        );

        $this->assertNotNull($asset->fresh()->sourceMedia());
        $this->assertSame('ready', $asset->fresh()->status);
        $this->assertSame('ready', $asset->conversionState());
        $this->assertNotNull($asset->optimizedUrl());
    }

    public function test_generated_public_webp_is_approved_automatically(): void
    {
        Storage::fake('public');
        $asset = StorefrontMediaAsset::create(['title' => 'تصویر فروشگاه', 'status' => 'pending']);
        $media = $this->attachMedia($asset);
        $this->writePreview($media, 1024);

        $this->assertTrue($asset->markReadyAfterUpload());
        $this->assertSame('ready', $asset->fresh()->status);
        $this->assertNotNull($asset->previewUrl());
    }

    public function test_missing_or_oversized_preview_stays_pending_without_an_exception(): void
    {
        Storage::fake('public');
        $asset = StorefrontMediaAsset::create(['title' => 'تصویر فروشگاه', 'status' => 'pending']);
        $media = $this->attachMedia($asset);

        $this->assertFalse($asset->markReadyAfterUpload());
        $this->assertSame('pending', $asset->fresh()->status);

        $this->writePreview($media, StorefrontMediaAsset::MAX_PUBLIC_BYTES + 1);
        $this->assertFalse($asset->markReadyAfterUpload());
        $this->assertSame('pending', $asset->fresh()->status);
    }

    private function attachMedia(StorefrontMediaAsset $asset): \Spatie\MediaLibrary\MediaCollections\Models\Media
    {
        return $asset->media()->create([
            'collection_name' => 'source',
            'name' => 'store',
            'file_name' => 'store.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 1024,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => ['preview' => true, 'thumb' => true],
            'responsive_images' => [],
        ]);
    }

    private function writePreview(\Spatie\MediaLibrary\MediaCollections\Models\Media $media, int $bytes): void
    {
        $path = $media->getPath('preview');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        file_put_contents($path, str_repeat('x', $bytes));
    }
}
