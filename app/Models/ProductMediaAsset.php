<?php

namespace App\Models;

use App\Enums\EvidenceState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductMediaAsset extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'product_id',
        'color_id',
        'variant_id',
        'role',
        'alt_text',
        'verification_state',
        'sort_order',
    ];

    protected $casts = [
        'verification_state' => EvidenceState::class,
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $asset): void {
            $asset->public_id ??= (string) Str::ulid();
        });

        static::saving(function (self $asset): void {
            if ($asset->variant_id !== null) {
                $variant = ProductVariant::query()->find($asset->variant_id);

                if ($variant !== null && (int) $variant->product_id !== (int) $asset->product_id) {
                    throw new \DomainException('Media variant must belong to the selected product.');
                }

                if (
                    $variant !== null
                    && $asset->color_id !== null
                    && $variant->color_id !== null
                    && (int) $variant->color_id !== (int) $asset->color_id
                ) {
                    throw new \DomainException('Media color must match the selected variant color.');
                }
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('asset')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
