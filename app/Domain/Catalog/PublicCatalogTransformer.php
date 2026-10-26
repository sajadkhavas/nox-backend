<?php

namespace App\Domain\Catalog;

use App\Enums\EvidenceState;
use App\Enums\ProductFact;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Drop;
use App\Models\Product;
use App\Models\ProductMediaAsset;
use App\Models\ProductVariant;
use App\Models\SizeGuide;
use Illuminate\Support\Collection as SupportCollection;

final class PublicCatalogTransformer
{
    public function productSummary(Product $product): array
    {
        $variants = $product->activeVariants;
        $prices = $variants->map(fn (ProductVariant $variant): int => $variant->current_price_toman);

        return [
            'publicId' => $product->public_id,
            'slug' => $product->slug,
            'name' => $product->name,
            'shortDescription' => $this->verified($product, ProductFact::Description)
                ? $product->short_description
                : null,
            'category' => $this->category($product->category),
            'price' => [
                'from' => $prices->isEmpty() ? null : $this->money((int) $prices->min()),
                'to' => $prices->isEmpty() ? null : $this->money((int) $prices->max()),
            ],
            'availability' => $variants->contains(fn (ProductVariant $variant): bool => $this->variantAvailable($variant)),
            'stockState' => $this->productStockState($variants),
            'colors' => $variants->pluck('color')->filter()->unique('public_id')->values()
                ->map(fn ($color): array => $this->color($color))->all(),
            'sizes' => $variants->pluck('size')->filter()->unique('public_id')->values()
                ->map(fn ($size): array => $this->size($size))->all(),
            'primaryImage' => $this->primaryImage($product),
            'previewImages' => $this->previewImages($product),
            'mannequin' => $this->mannequin($product),
            'seo' => $this->productSeo($product),
        ];
    }

    public function productDetail(Product $product): array
    {
        $variants = $product->activeVariants;
        $verifiedCollectionMembership = $this->verified($product, ProductFact::CollectionMembership);

        return [
            ...$this->productSummary($product),
            'description' => $this->verified($product, ProductFact::Description)
                ? $product->description
                : null,
            'publication' => 'published',
            'collections' => $verifiedCollectionMembership
                ? $product->collections->map(fn (Collection $collection): array => $this->collection($collection))->all()
                : [],
            'drops' => $product->drops->map(fn (Drop $drop): array => $this->drop($drop))->all(),
            'variants' => $variants->map(fn (ProductVariant $variant): array => $this->variant($product, $variant))->all(),
            'media' => $this->media($product),
            'material' => $this->verified($product, ProductFact::Material) ? $product->material : null,
            'fabricComposition' => $this->verified($product, ProductFact::Material) ? $product->fabric_composition : null,
            'fit' => $this->verified($product, ProductFact::Fit) ? $product->fit : null,
            'care' => $this->verified($product, ProductFact::Care) ? $product->care_instructions : null,
            'sizeGuide' => $this->verified($product, ProductFact::SizeGuide)
                ? $this->sizeGuide($product->sizeGuide)
                : null,
            'breadcrumbs' => array_values(array_filter([
                ['label' => 'محصولات', 'path' => '/shop'],
                $product->category ? [
                    'label' => $product->category->name,
                    'path' => '/'.$product->category->slug,
                ] : null,
                ['label' => $product->name, 'path' => '/product/'.$product->slug],
            ])),
        ];
    }

    public function category(Category $category, ?int $productCount = null): array
    {
        return array_filter([
            'publicId' => $category->public_id,
            'parentPublicId' => $category->parent?->public_id,
            'depth' => $category->depth,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image' => $category->image_path ? asset('storage/'.ltrim($category->image_path, '/')) : null,
            'icon' => $category->icon_path ? asset('storage/'.ltrim($category->icon_path, '/')) : null,
            'showInHeader' => (bool) $category->show_in_header,
            'showOnHome' => (bool) $category->show_on_home,
            'productCount' => $productCount,
            'seo' => [
                'metaTitle' => $category->meta_title,
                'metaDescription' => $category->meta_description,
                'slug' => $category->slug,
                'canonicalPath' => '/'.$category->slug,
                'publication' => 'published',
            ],
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function collection(Collection $collection, ?int $productCount = null): array
    {
        return array_filter([
            'publicId' => $collection->public_id,
            'name' => $collection->name,
            'slug' => $collection->slug,
            'description' => $collection->description,
            'isFeatured' => $collection->is_featured,
            'productCount' => $productCount,
            'seo' => [
                'metaTitle' => $collection->meta_title,
                'metaDescription' => $collection->meta_description,
                'slug' => $collection->slug,
                'canonicalPath' => '/collections/'.$collection->slug,
                'publication' => 'published',
            ],
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function drop(Drop $drop): array
    {
        return [
            'publicId' => $drop->public_id,
            'name' => $drop->name,
            'slug' => $drop->slug,
            'description' => $drop->description,
            'startsAt' => $drop->starts_at?->toIso8601String(),
            'endsAt' => $drop->ends_at?->toIso8601String(),
            'isFeatured' => $drop->is_featured,
            'seo' => [
                'metaTitle' => $drop->meta_title,
                'metaDescription' => $drop->meta_description,
                'slug' => $drop->slug,
                'canonicalPath' => '/drops/'.$drop->slug,
                'publication' => 'published',
            ],
        ];
    }

    public function color($color): array
    {
        return [
            'publicId' => $color->public_id,
            'name' => $color->name,
            'slug' => $color->slug,
            'code' => $color->code,
            'hex' => $color->hex,
        ];
    }

    public function size($size): array
    {
        return [
            'publicId' => $size->public_id,
            'name' => $size->name,
            'code' => $size->code,
        ];
    }

    public function variant(Product $product, ProductVariant $variant): array
    {
        $mediaIds = $product->mediaAssets
            ->where('variant_id', $variant->id)
            ->pluck('public_id')
            ->values()
            ->all();

        return [
            'publicId' => $variant->public_id,
            'sku' => $this->verified($product, ProductFact::Sku) ? $variant->sku : null,
            'color' => $variant->color ? $this->color($variant->color) : null,
            'size' => $variant->size ? $this->size($variant->size) : null,
            'price' => $this->money($variant->current_price_toman),
            'compareAtPrice' => $this->verified($product, ProductFact::PreviousPrice)
                && $variant->previous_price_toman !== null
                    ? $this->money($variant->previous_price_toman)
                    : null,
            'availability' => $this->variantAvailable($variant),
            'stockState' => $this->variantStockState($variant),
            'isActive' => true,
            'mediaPublicIds' => $mediaIds,
        ];
    }

    public function media(Product $product): array
    {
        if (! $this->verified($product, ProductFact::Media)) {
            return [];
        }

        $activeVariantIds = $product->activeVariants->pluck('id')->flip();

        return $product->mediaAssets
            ->map(function (ProductMediaAsset $asset) use ($activeVariantIds): ?array {
                $media = $asset->getFirstMedia('asset');

                if ($media === null) {
                    return null;
                }

                return [
                    'publicId' => $asset->public_id,
                    'role' => $asset->role,
                    'sortOrder' => $asset->sort_order,
                    'alt' => $asset->alt_text,
                    'width' => $media->getCustomProperty('width'),
                    'height' => $media->getCustomProperty('height'),
                    'colorPublicId' => $asset->color?->is_active === true ? $asset->color->public_id : null,
                    'variantPublicId' => $asset->variant_id !== null && $activeVariantIds->has($asset->variant_id)
                        ? $asset->variant?->public_id
                        : null,
                    'url' => $media->getUrl(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function sizeGuide(?SizeGuide $guide): ?array
    {
        if ($guide === null || ! $guide->is_active) {
            return null;
        }

        $measurements = $guide->measurements
            ->filter(fn ($measurement): bool => $measurement->size !== null && $measurement->definition !== null);

        return [
            'publicId' => $guide->public_id,
            'name' => $guide->name,
            'description' => $guide->description,
            'unit' => $guide->unit,
            'definitions' => $measurements->pluck('definition')->unique('public_id')->values()
                ->map(fn ($definition): array => [
                    'publicId' => $definition->public_id,
                    'code' => $definition->code,
                    'label' => $definition->label,
                ])->all(),
            'sizes' => $measurements->groupBy('size.public_id')->map(function (SupportCollection $rows): array {
                $size = $rows->first()->size;

                return [
                    'size' => $this->size($size),
                    'measurements' => $rows->map(fn ($row): array => [
                        'definitionPublicId' => $row->definition->public_id,
                        'code' => $row->definition->code,
                        'value' => (string) $row->value,
                        'notes' => $row->notes,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    private function mannequin(Product $product): array
    {
        $slots = (array) config('mannequin.slots', []);
        $presets = (array) config('mannequin.presets', []);
        $slot = is_string($product->mannequin_slot) && array_key_exists($product->mannequin_slot, $slots)
            ? $product->mannequin_slot
            : null;

        $slotPreset = $slot !== null ? ($slots[$slot]['preset'] ?? null) : null;
        $requestedPreset = is_string($product->mannequin_preset) ? $product->mannequin_preset : null;
        $preset = $requestedPreset !== null && array_key_exists($requestedPreset, $presets)
            ? $requestedPreset
            : (is_string($slotPreset) && array_key_exists($slotPreset, $presets) ? $slotPreset : null);
        $profile = $preset !== null ? (array) $presets[$preset] : [];
        $assetUrl = $product->getFirstMediaUrl('mannequin-front') ?: null;

        return [
            'enabled' => (bool) $product->mannequin_enabled && $slot !== null && $assetUrl !== null,
            'assetUrl' => $assetUrl,
            'slot' => $slot,
            'offsetX' => $this->boundedFloat(
                $product->mannequin_offset_x,
                (float) ($profile['offset_x'] ?? 0.0),
                'offset_x',
            ),
            'offsetY' => $this->boundedFloat(
                $product->mannequin_offset_y,
                (float) ($profile['offset_y'] ?? 0.0),
                'offset_y',
            ),
            'scale' => $this->boundedFloat(
                $product->mannequin_scale,
                (float) ($profile['scale'] ?? 1.0),
                'scale',
            ),
            'layer' => $this->boundedInt(
                $product->mannequin_layer,
                (int) ($profile['layer'] ?? 30),
                'layer',
            ),
            'preset' => $preset,
        ];
    }

    private function boundedFloat(mixed $value, float $fallback, string $key): float
    {
        $bounds = (array) config("mannequin.bounds.{$key}", []);
        $min = isset($bounds[0]) ? (float) $bounds[0] : -100.0;
        $max = isset($bounds[1]) ? (float) $bounds[1] : 100.0;
        $resolved = is_numeric($value) ? (float) $value : $fallback;

        return max($min, min($max, $resolved));
    }

    private function boundedInt(mixed $value, int $fallback, string $key): int
    {
        $bounds = (array) config("mannequin.bounds.{$key}", []);
        $min = isset($bounds[0]) ? (int) $bounds[0] : 1;
        $max = isset($bounds[1]) ? (int) $bounds[1] : 100;
        $resolved = is_numeric($value) ? (int) $value : $fallback;

        return max($min, min($max, $resolved));
    }

    private function productSeo(Product $product): array
    {
        $description = $this->verified($product, ProductFact::Description)
            ? ($product->short_description ?: $product->description)
            : null;

        $variants = $product->activeVariants;
        $prices = $variants->map(fn (ProductVariant $variant): int => $variant->current_price_toman);

        return [
            'metaTitle' => $product->meta_title,
            'metaDescription' => $product->meta_description,
            'slug' => $product->slug,
            'canonicalPath' => '/product/'.$product->slug,
            'publication' => 'published',
            'primaryImage' => $this->primaryImage($product),
            'updatedAt' => $product->updated_at?->toIso8601String(),
            'breadcrumbs' => [
                ['label' => 'محصولات', 'path' => '/shop'],
                ...($product->category ? [[
                    'label' => $product->category->name,
                    'path' => '/'.$product->category->slug,
                ]] : []),
                ['label' => $product->name, 'path' => '/product/'.$product->slug],
            ],
            'structuredData' => [
                'name' => $product->name,
                'description' => $description,
                'priceCurrency' => 'TOMAN',
                'lowPrice' => $prices->isEmpty() ? null : (int) $prices->min(),
                'highPrice' => $prices->isEmpty() ? null : (int) $prices->max(),
                'availability' => $variants->contains(fn (ProductVariant $variant): bool => $this->variantAvailable($variant))
                    ? 'in_stock'
                    : 'out_of_stock',
            ],
        ];
    }

    private function primaryImage(Product $product): ?string
    {
        return $this->previewImages($product)[0] ?? null;
    }

    /** @return list<string> */
    private function previewImages(Product $product): array
    {
        if (! $this->verified($product, ProductFact::Media)) {
            return [];
        }

        return $product->mediaAssets
            ->sortBy(fn (ProductMediaAsset $asset): array => [(int) $asset->sort_order, (int) $asset->getKey()])
            ->map(fn (ProductMediaAsset $asset): ?string => $asset->getFirstMedia('asset')?->getUrl())
            ->filter()
            ->unique()
            ->take(3)
            ->values()
            ->all();
    }

    private function verified(Product $product, ProductFact $fact): bool
    {
        return $product->evidences->contains(
            fn ($evidence): bool => $evidence->fact_key === $fact->value
                && $evidence->state === EvidenceState::Verified,
        );
    }

    private function money(int $amount): array
    {
        return [
            'amount' => $amount,
            'currency' => 'TOMAN',
        ];
    }

    private function variantStockState(ProductVariant $variant): string
    {
        if (! $this->variantIdentityIsSellable($variant)) {
            return 'unavailable';
        }

        if ($variant->available_quantity <= 0) {
            return 'out_of_stock';
        }

        return $variant->low_stock ? 'low_stock' : 'in_stock';
    }

    private function variantAvailable(ProductVariant $variant): bool
    {
        return $this->variantIdentityIsSellable($variant) && $variant->available_quantity > 0;
    }

    private function variantIdentityIsSellable(ProductVariant $variant): bool
    {
        return $variant->is_active
            && filled($variant->sku)
            && $variant->color?->is_active === true
            && $variant->size?->is_active === true;
    }

    private function productStockState(SupportCollection $variants): string
    {
        if ($variants->isEmpty()) {
            return 'unavailable';
        }

        if ($variants->every(fn (ProductVariant $variant): bool => $variant->available_quantity <= 0)) {
            return 'out_of_stock';
        }

        if ($variants->filter(fn (ProductVariant $variant): bool => $this->variantAvailable($variant))->every(
            fn (ProductVariant $variant): bool => $variant->low_stock,
        )) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
