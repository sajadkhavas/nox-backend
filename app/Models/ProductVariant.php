<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'color_id', 'size_id', 'name', 'sku', 'regular_price_toman', 'sale_price_toman',
        'stock_quantity', 'low_stock_threshold', 'is_default', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'regular_price_toman' => 'integer', 'sale_price_toman' => 'integer', 'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer', 'active_reserved_quantity' => 'integer', 'is_default' => 'boolean',
        'is_active' => 'boolean', 'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $variant): void {
            $variant->public_id ??= (string) Str::ulid();
            self::validatePrices($variant);
            self::validatePublishedIdentity($variant);
        });
        static::updating(function (self $variant): void {
            self::validatePrices($variant);
            self::validatePublishedIdentity($variant);
            if ($variant->isDirty('stock_quantity') && ! app()->bound('lbb.inventory_ledger_mutation')) {
                throw new \DomainException('Stock mutations must use InventoryLedgerService.');
            }
        });
        static::saved(function (self $variant): void {
            if ($variant->is_default) {
                self::query()->where('product_id', $variant->product_id)->whereKeyNot($variant->getKey())
                    ->where('is_default', true)->update(['is_default' => false]);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(ProductMediaAsset::class, 'variant_id');
    }

    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'variant_id');
    }

    public function inventoryLedgerEntries(): HasMany
    {
        return $this->hasMany(InventoryLedgerEntry::class, 'variant_id')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSellable(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('color_id')->whereNotNull('size_id')
            ->whereNotNull('sku')->where('sku', '<>', '')
            ->whereHas('color', fn (Builder $color): Builder => $color->active())
            ->whereHas('size', fn (Builder $size): Builder => $size->active());
    }

    public function getCurrentPriceTomanAttribute(): int
    {
        return $this->hasValidSalePrice() ? (int) $this->sale_price_toman : (int) $this->regular_price_toman;
    }

    public function getPreviousPriceTomanAttribute(): ?int
    {
        return $this->hasValidSalePrice() ? (int) $this->regular_price_toman : null;
    }

    public function getStockOnHandAttribute(): int
    {
        return (int) $this->stock_quantity;
    }

    public function getReservedQuantityAttribute(): int
    {
        if (array_key_exists('active_reserved_quantity', $this->attributes)) {
            return (int) ($this->attributes['active_reserved_quantity'] ?? 0);
        }

        return (int) $this->inventoryReservations()->active()->sum('quantity');
    }

    public function getAvailableStockQuantityAttribute(): int
    {
        return max(0, $this->stock_on_hand - $this->reserved_quantity);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return $this->available_stock_quantity;
    }

    public function getAvailableAttribute(): bool
    {
        return $this->is_sellable && $this->available_quantity > 0;
    }

    public function getIsSellableAttribute(): bool
    {
        if (! $this->is_active || $this->color_id === null || $this->size_id === null || blank($this->sku)) {
            return false;
        }
        if ($this->relationLoaded('color') && $this->color?->is_active !== true) {
            return false;
        }
        if ($this->relationLoaded('size') && $this->size?->is_active !== true) {
            return false;
        }

        return Color::query()->whereKey($this->color_id)->active()->exists() && Size::query()->whereKey($this->size_id)->active()->exists();
    }

    public function getLowStockAttribute(): bool
    {
        return $this->available_quantity > 0 && $this->available_quantity <= $this->low_stock_threshold;
    }

    public function hasValidSalePrice(): bool
    {
        return $this->sale_price_toman !== null && $this->sale_price_toman > 0 && $this->sale_price_toman < $this->regular_price_toman;
    }

    private static function validatePrices(self $variant): void
    {
        if ($variant->regular_price_toman < 1) {
            throw new InvalidArgumentException('قیمت عادی Variant باید بیشتر از صفر باشد.');
        }
        if ($variant->sale_price_toman !== null && $variant->sale_price_toman >= $variant->regular_price_toman) {
            throw new InvalidArgumentException('قیمت فروش باید کمتر از قیمت عادی باشد.');
        }
    }

    private static function validatePublishedIdentity(self $variant): void
    {
        if (! $variant->is_active) {
            return;
        }
        $product = $variant->relationLoaded('product') ? $variant->product : Product::query()->find($variant->product_id);
        if ($product?->publication_status !== PublicationStatus::Published) {
            return;
        }
        if ($variant->color_id === null || $variant->size_id === null || blank($variant->sku)) {
            throw new \DomainException('Published apparel variants require color, size, and SKU.');
        }
        if (! Color::query()->whereKey($variant->color_id)->active()->exists() || ! Size::query()->whereKey($variant->size_id)->active()->exists()) {
            throw new \DomainException('Published apparel variants require active color and size entities.');
        }
    }
}
