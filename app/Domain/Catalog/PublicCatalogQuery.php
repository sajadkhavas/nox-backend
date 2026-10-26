<?php

namespace App\Domain\Catalog;

use App\Enums\EvidenceState;
use App\Enums\InventoryReservationStatus;
use App\Enums\ProductFact;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class PublicCatalogQuery
{
    public const SORTS = ['newest', 'price_asc', 'price_desc'];

    public const AVAILABILITY = ['in_stock', 'out_of_stock'];

    public function products(array $filters): LengthAwarePaginator
    {
        $query = $this->applyFilters($this->publishedProducts(), $filters);

        $this->applySort($query, (string) ($filters['sort'] ?? 'newest'));

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? config('lbb.policies.pagination.catalog_default', 12)),
            page: (int) ($filters['page'] ?? 1),
        )->withQueryString();
    }

    public function product(string $slug): Product
    {
        return $this->publishedProducts()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function productsForCollection(string $slug, array $filters): array
    {
        $collection = Collection::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $filters['collection'] = $slug;

        return [$collection, $this->products($filters)];
    }

    public function facets(): array
    {
        $published = $this->publishedProducts()->select('products.id');

        $categories = Category::query()
            ->published()
            ->active()
            ->whereHas('products', fn (Builder $products): Builder => $products->whereIn('products.id', clone $published))
            ->ordered()
            ->get(['public_id', 'name', 'slug']);

        $collections = Collection::query()
            ->published()
            ->whereHas('products', fn (Builder $products): Builder => $products
                ->whereIn('products.id', clone $published)
                ->whereHas('evidences', static fn (Builder $evidence): Builder => $evidence
                    ->where('fact_key', ProductFact::CollectionMembership->value)
                    ->where('state', EvidenceState::Verified->value)))
            ->ordered()
            ->get(['public_id', 'name', 'slug']);

        $colors = Color::query()
            ->active()
            ->whereHas('variants', function (Builder $variants) use ($published): void {
                $variants->sellable()->whereIn('product_id', clone $published);
            })
            ->ordered()
            ->get(['public_id', 'name', 'slug', 'code', 'hex']);

        $sizes = Size::query()
            ->active()
            ->whereHas('variants', function (Builder $variants) use ($published): void {
                $variants->sellable()->whereIn('product_id', clone $published);
            })
            ->ordered()
            ->get(['public_id', 'name', 'code']);

        $price = ProductVariant::query()
            ->sellable()
            ->whereIn('product_id', clone $published)
            ->selectRaw(
                'MIN(CASE WHEN sale_price_toman IS NOT NULL AND sale_price_toman > 0 AND sale_price_toman < regular_price_toman THEN sale_price_toman ELSE regular_price_toman END) AS min_price'
            )
            ->selectRaw(
                'MAX(CASE WHEN sale_price_toman IS NOT NULL AND sale_price_toman > 0 AND sale_price_toman < regular_price_toman THEN sale_price_toman ELSE regular_price_toman END) AS max_price'
            )
            ->first();

        return [
            'categories' => $categories,
            'collections' => $collections,
            'colors' => $colors,
            'sizes' => $sizes,
            'price' => [
                'min' => $price?->min_price === null ? null : (int) $price->min_price,
                'max' => $price?->max_price === null ? null : (int) $price->max_price,
            ],
            'availability' => self::AVAILABILITY,
            'sorts' => self::SORTS,
        ];
    }

    public function publishedProducts(): Builder
    {
        $query = Product::query()
            ->published()
            ->where('is_active', true)
            ->where(function (Builder $published): void {
                $published->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->whereHas('category', fn (Builder $category): Builder => $category->published()->active())
            ->whereHas('activeVariants', fn (Builder $variants): Builder => $variants->sellable());

        foreach ([
            ProductFact::Name,
            ProductFact::Media,
            ProductFact::Price,
            ProductFact::Colors,
            ProductFact::Sizes,
            ProductFact::Stock,
            ProductFact::Sku,
        ] as $fact) {
            $query->whereHas('evidences', static function (Builder $evidence) use ($fact): void {
                $evidence
                    ->where('fact_key', $fact->value)
                    ->where('state', EvidenceState::Verified->value);
            });
        }

        return $query->with([
            'category',
            'collections' => fn ($collections) => $collections->published(),
            'drops' => fn ($drops) => $drops->published(),
            'evidences',
            'media' => fn ($media) => $media->where('collection_name', 'mannequin-front'),
            'mediaAssets' => fn ($media) => $media
                ->where('verification_state', EvidenceState::Verified->value)
                ->with(['media', 'color', 'variant']),
            'sizeGuide' => fn ($guide) => $guide
                ->active()
                ->with([
                    'measurements' => fn ($measurements) => $measurements
                        ->with([
                            'size' => fn ($size) => $size->active(),
                            'definition' => fn ($definition) => $definition->active(),
                        ]),
                ]),
            'activeVariants' => fn ($variants) => $variants
                ->sellable()
                ->with(['color', 'size'])
                ->withSum([
                    'inventoryReservations as active_reserved_quantity' => fn ($reservations) => $reservations->active(),
                ], 'quantity'),
        ]);
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $categories = $this->csv($filters['category'] ?? null);
        if ($categories !== []) {
            $query->whereHas('category', fn (Builder $category): Builder => $category->whereIn('slug', $categories));
        }

        $collections = $this->csv($filters['collection'] ?? null);
        if ($collections !== []) {
            $query
                ->whereHas('collections', fn (Builder $collection): Builder => $collection
                    ->published()
                    ->whereIn('slug', $collections))
                ->whereHas('evidences', static fn (Builder $evidence): Builder => $evidence
                    ->where('fact_key', ProductFact::CollectionMembership->value)
                    ->where('state', EvidenceState::Verified->value));
        }

        $colors = $this->csv($filters['color'] ?? null);
        $sizes = $this->csv($filters['size'] ?? null);

        if (filled($filters['q'] ?? null)) {
            $needle = trim((string) $filters['q']);
            $query->where(static function (Builder $search) use ($needle): void {
                $search
                    ->where('name', 'like', '%'.$needle.'%')
                    ->orWhere('slug', 'like', '%'.$needle.'%');
            });
        }

        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;

        if (
            $colors !== [] || $sizes !== [] || $minPrice !== null || $maxPrice !== null
            || ($filters['availability'] ?? null) === 'in_stock'
        ) {
            // All choices must match the same purchasable variant.
            $query->whereHas('activeVariants', function (Builder $variants) use ($colors, $sizes, $minPrice, $maxPrice, $filters): void {
                $variants->sellable();

                if ($colors !== []) {
                    $variants->whereHas('color', fn (Builder $color): Builder => $color
                        ->active()
                        ->whereIn('slug', $colors));
                }

                if ($sizes !== []) {
                    $variants->whereHas('size', fn (Builder $size): Builder => $size
                        ->active()
                        ->whereIn('code', $sizes));
                }

                if ($minPrice !== null || $maxPrice !== null) {
                    $this->whereCurrentPrice($variants, $minPrice, $maxPrice);
                }

                if (($filters['availability'] ?? null) === 'in_stock') {
                    $this->whereAvailable($variants, true);
                }
            });
        }

        if (($filters['availability'] ?? null) === 'out_of_stock') {
            $query->whereDoesntHave('activeVariants', fn (Builder $variants): Builder => $this->whereAvailable($variants, true));
        }

        return $query;
    }

    private function applySort(Builder $query, string $sort): void
    {
        if ($sort === 'price_asc' || $sort === 'price_desc') {
            $query->addSelect([
                'catalog_price_toman' => ProductVariant::query()
                    ->selectRaw(
                        'MIN(CASE WHEN sale_price_toman IS NOT NULL AND sale_price_toman > 0 AND sale_price_toman < regular_price_toman THEN sale_price_toman ELSE regular_price_toman END)'
                    )
                    ->whereColumn('product_id', 'products.id')
                    ->sellable()
                    ->limit(1),
            ])->orderBy('catalog_price_toman', $sort === 'price_asc' ? 'asc' : 'desc')
                ->orderBy('products.id');

            return;
        }

        $query->orderByDesc('published_at')
            ->orderByDesc('products.id');
    }

    private function whereCurrentPrice(Builder $query, mixed $minPrice, mixed $maxPrice): void
    {
        $expression = 'CASE WHEN sale_price_toman IS NOT NULL AND sale_price_toman > 0 AND sale_price_toman < regular_price_toman THEN sale_price_toman ELSE regular_price_toman END';

        if ($minPrice !== null) {
            $query->whereRaw($expression.' >= ?', [(int) $minPrice]);
        }

        if ($maxPrice !== null) {
            $query->whereRaw($expression.' <= ?', [(int) $maxPrice]);
        }
    }

    private function whereAvailable(Builder $query, bool $available): Builder
    {
        $operator = $available ? '>' : '<=';

        return $query->whereRaw(
            'stock_quantity - COALESCE((SELECT SUM(quantity) FROM inventory_reservations WHERE inventory_reservations.variant_id = product_variants.id AND inventory_reservations.status = ? AND inventory_reservations.expires_at > ?), 0) '.$operator.' 0',
            [InventoryReservationStatus::Active->value, now()],
        );
    }

    /** @return list<string> */
    private function csv(mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode(',', $value),
        ), static fn (string $part): bool => $part !== '')));
    }
}
