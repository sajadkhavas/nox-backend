<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class StorefrontMediaAsset extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    public const MAX_PUBLIC_BYTES = 1_048_576;

    protected $fillable = ['title', 'alt_text', 'usage', 'status', 'notes'];

    protected static function booted(): void
    {
        static::saving(function (self $asset): void {
            if ($asset->isDirty('status') && $asset->status === 'ready' && ! $asset->isReady()) {
                throw new \DomainException('نسخه WebP باید آماده، عمومی و حداکثر ۱ مگابایت باشد.');
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('source')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->performOnCollections('source')
            ->fit(Fit::Crop, 240, 240)->format('webp')->quality(78)->nonQueued();
        $this->addMediaConversion('preview')->performOnCollections('source')
            ->fit(Fit::Max, 1200, 1200)->format('webp')->quality(80)->nonQueued();
    }

    public function sourceMedia(): ?Media
    {
        return $this->getFirstMedia('source');
    }

    public static function createFromUpload(UploadedFile $file, array $attributes): self
    {
        $asset = self::query()->create(array_merge($attributes, ['status' => 'pending']));

        try {
            $asset->addMedia($file)
                ->usingName($asset->title)
                ->toMediaCollection('source', 'public');

            $asset->unsetRelation('media');
            if ($asset->sourceMedia() === null) {
                throw new \RuntimeException('فایل اصلی به رکورد تصویر وصل نشد.');
            }

            $asset->markReadyAfterUpload();

            return $asset;
        } catch (Throwable $exception) {
            $asset->forceDelete();

            throw $exception;
        }
    }

    public function isReady(): bool
    {
        try {
            $media = $this->sourceMedia();
            if (! $media || ! $media->hasGeneratedConversion('preview') || ! $media->hasGeneratedConversion('thumb')) {
                return false;
            }
            if (($media->conversions_disk ?: $media->disk) !== 'public') {
                return false;
            }
            $path = $media->getPath('preview');

            return is_file($path) && filesize($path) <= self::MAX_PUBLIC_BYTES;
        } catch (Throwable) {
            return false;
        }
    }

    public function conversionState(): string
    {
        try {
            $media = $this->sourceMedia();
            if ($media === null) return 'missing';
            if (! $media->hasGeneratedConversion('preview') || ! $media->hasGeneratedConversion('thumb')) return 'pending';
            if (! is_file($media->getPath('preview'))) return 'broken';

            return $this->isReady() ? 'ready' : 'oversized';
        } catch (Throwable) {
            return 'broken';
        }
    }

    public function originalUrl(): ?string
    {
        try { return $this->sourceMedia()?->getFullUrl(); } catch (Throwable) { return null; }
    }

    public function optimizedUrl(): ?string
    {
        try {
            $media = $this->sourceMedia();
            return $media?->hasGeneratedConversion('preview') ? $media->getFullUrl('preview') : null;
        } catch (Throwable) { return null; }
    }

    public function originalSizeLabel(): string
    {
        try { return self::humanBytes($this->sourceMedia()?->size); } catch (Throwable) { return '—'; }
    }

    public function optimizedSizeLabel(): string
    {
        try {
            $media = $this->sourceMedia();
            if (! $media?->hasGeneratedConversion('preview')) return '—';
            $path = $media->getPath('preview');
            return is_file($path) ? self::humanBytes(filesize($path)) : '—';
        } catch (Throwable) { return '—'; }
    }

    public function dimensionsLabel(): string
    {
        try {
            $path = $this->sourceMedia()?->getPath();
            if (! $path || ! is_file($path)) return '—';
            $dimensions = @getimagesize($path);
            return $dimensions ? $dimensions[0].'×'.$dimensions[1].' px' : 'نامشخص';
        } catch (Throwable) { return 'نامشخص'; }
    }

    public function formatLabel(): string
    {
        try { return $this->sourceMedia()?->mime_type ?? '—'; } catch (Throwable) { return '—'; }
    }

    private static function humanBytes(?int $bytes): string
    {
        if ($bytes === null) return '—';
        if ($bytes < 1024) return $bytes.' B';
        if ($bytes < 1048576) return number_format($bytes / 1024, 1).' KB';
        return number_format($bytes / 1048576, 2).' MB';
    }

    public function markReadyAfterUpload(): bool
    {
        if ($this->status !== 'pending') {
            return $this->status === 'ready' && $this->isReady();
        }

        // Filament saves the media relationship after creating the asset record.
        $this->unsetRelation('media');
        if (! $this->isReady()) {
            return false;
        }

        $this->forceFill(['status' => 'ready'])->save();

        return true;
    }

    public function previewUrl(): ?string
    {
        return $this->isReady() ? $this->sourceMedia()?->getFullUrl('preview') : null;
    }

    public function previewPath(): ?string
    {
        return $this->isReady() ? $this->sourceMedia()?->getPathRelativeToRoot('preview') : null;
    }
}
