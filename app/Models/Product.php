<?php

namespace App\Models;

use App\Domain\Apparel\ApparelPublicationGuard;
use App\Domain\Catalog\MannequinModel3d;
use App\Enums\PublicationStatus;
use Illuminate\Cache\TaggableStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model implements HasMedia
{
    use HasSlug, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'category_id',
        'size_guide_id',
        'name',
        'slug',
        'product_code',
        'short_description',
        'description',
        'material',
        'fabric_composition',
        'fit',
        'care_instructions',
        'publication_status',
        'published_at',
        'content_verified',
        'media_verified',
        'is_active',
        'is_featured',
        'sort_order',
        'meta_title',
        'meta_description',
        'mannequin_enabled',
        'mannequin_slot',
        'mannequin_preset',
        'mannequin_offset_x',
        'mannequin_offset_y',
        'mannequin_scale',
        'mannequin_layer',
    ];

    protected $casts = [
        'publication_status' => PublicationStatus::class,
        'published_at' => 'datetime',
        'content_verified' => 'boolean',
        'media_verified' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'mannequin_enabled' => 'boolean',
        'mannequin_offset_x' => 'float',
        'mannequin_offset_y' => 'float',
        'mannequin_scale' => 'float',
        'mannequin_layer' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $product): void {
            $product->public_id ??= (string) Str::ulid();
        });

        static::saving(function (self $product): void {
            if (
                $product->exists
                && $product->getRawOriginal('publication_status') === PublicationStatus::Published->value
                && $product->publication_status === PublicationStatus::Published
                && $product->hasVerifiedFactMutation()
            ) {
                throw new \DomainException(
                    'Set a published product back to draft before changing evidence-tracked apparel facts.',
                );
            }

            if (
                $product->publication_status === PublicationStatus::Published
                && $product->isDirty('publication_status')
            ) {
                app(ApparelPublicationGuard::class)->assertPublishable($product);
                $product->published_at ??= now();
            }
        });

        static::saved(function (self $product): void {
            self::flushCatalogCache($product->slug);
        });

        static::deleted(function (self $product): void {
            self::flushCatalogCache($product->slug);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'product_code',
                'category_id',
                'size_guide_id',
                'publication_status',
                'content_verified',
                'media_verified',
                'is_active',
                'is_featured',
                'mannequin_enabled',
                'mannequin_slot',
                'mannequin_preset',
                'mannequin_offset_x',
                'mannequin_offset_y',
                'mannequin_scale',
                'mannequin_layer',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "محصول {$this->name} {$eventName} شد");
    }

    public function registerMediaCollections(): void
    {
        // Kept for backward compatibility with the neutral catalog API.
        // Apparel-specific color/variant associations live on ProductMediaAsset.
        $this->addMediaCollection('catalog-main')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);

        $this->addMediaCollection('catalog-gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);

        // FC1 uses a dedicated, front-facing transparent cutout. JPEG is intentionally
        // excluded because the mannequin composition requires an alpha-capable asset.
        $this->addMediaCollection('mannequin-front')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/webp', 'image/avif']);

        // Phase 2 keeps the 3D model as optional product media: no schema mutation is
        // required and products without a validated model continue to use the 2D view.
        $this->addMediaCollection(MannequinModel3d::COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes((array) config('mannequin.model3d.mime_types', [
                'model/gltf-binary',
                'application/gltf-buffer',
                'application/octet-stream',
            ]));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function sizeGuide(): BelongsTo
    {
        return $this->belongsTo(SizeGuide::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id')
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function apparelVariants(): HasMany
    {
        return $this->variants()
            ->whereNotNull('color_id')
            ->whereNotNull('size_id');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function drops(): BelongsToMany
    {
        return $this->belongsToMany(Drop::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(ProductEvidence::class);
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(ProductMediaAsset::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        // Legacy neutral-catalog compatibility. BE-D owns the public API cutover
        // to publication_status once the SSR/public contract is frozen.
        return $query->where('is_active', true)
            ->whereHas('category', fn (Builder $category): Builder => $category->active())
            ->whereHas('activeVariants');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publication_status', PublicationStatus::Published->value);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    private function hasVerifiedFactMutation(): bool
    {
        return $this->isDirty([
            'name',
            'short_description',
            'description',
            'material',
            'fabric_composition',
            'fit',
            'care_instructions',
        ]);
    }

    private static function flushCatalogCache(string $slug): void
    {
        Cache::forget("catalog.product.{$slug}");

        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(['catalog'])->flush();
        }
    }
}
