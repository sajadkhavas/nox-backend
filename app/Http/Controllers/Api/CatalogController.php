<?php

namespace App\Http\Controllers\Api;

use App\Enums\InventoryReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Support\ApiResponse;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:140'],
            'search' => ['nullable', 'string', 'max:100'],
            'featured' => ['nullable', 'boolean'],
            'inStock' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:featured,newest,name,price-asc,price-desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:'.config('lbb.policies.pagination.catalog_max', 48)],
        ]);

        $featured = $request->has('featured')
            ? $request->boolean('featured')
            : false;
        $inStock = $request->has('inStock')
            ? $request->boolean('inStock')
            : false;

        $query = Product::query()
            ->active()
            ->with($this->catalogRelations());

        if (! empty($filters['category'])) {
            $query->whereHas(
                'category',
                fn (Builder $category): Builder => $category->where('slug', $filters['category']),
            );
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if ($featured) {
            $query->featured();
        }

        if ($inStock) {
            $query->whereHas('activeVariants', function (Builder $variant): void {
                $variant->whereRaw(
                    'stock_quantity > COALESCE((SELECT SUM(quantity) FROM inventory_reservations WHERE inventory_reservations.variant_id = product_variants.id AND status = ? AND expires_at > ?), 0)',
                    [InventoryReservationStatus::Active->value, now()],
                );
            });
        }

        $this->applySort($query, $filters['sort'] ?? 'featured');

        $paginator = $query->paginate((int) ($filters['perPage'] ?? config(
            'lbb.policies.pagination.catalog_default',
            12,
        )));
        $items = ProductResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::success($items, meta: [
            'pagination' => Pagination::meta($paginator),
            'filters' => [
                'category' => $filters['category'] ?? null,
                'search' => $filters['search'] ?? null,
                'featured' => $featured,
                'inStock' => $inStock,
                'sort' => $filters['sort'] ?? 'featured',
            ],
        ]);
    }

    public function product(string $slug): JsonResponse
    {
        $product = Product::query()
            ->active()
            ->with($this->catalogRelations())
            ->where('slug', $slug)
            ->firstOrFail();

        return ApiResponse::success(
            (new ProductResource($product))->resolve(),
        );
    }

    public function categories(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->withCount([
                'products' => fn (Builder $products): Builder => $products->active(),
            ])
            ->ordered()
            ->get();

        return ApiResponse::success(
            CategoryResource::collection($categories)->resolve($request),
        );
    }

    private function catalogRelations(): array
    {
        return [
            'category',
            'media',
            'activeVariants' => fn (HasMany $variants) => $variants->withSum([
                'inventoryReservations as active_reserved_quantity' => fn (Builder $reservations): Builder => $reservations->active(),
            ], 'quantity'),
        ];
    }

    private function applySort(Builder $query, string $sort): void
    {
        $currentPriceSql = <<<'SQL'
            (SELECT MIN(
                CASE
                    WHEN sale_price_toman IS NOT NULL
                        AND sale_price_toman > 0
                        AND sale_price_toman < regular_price_toman
                    THEN sale_price_toman
                    ELSE regular_price_toman
                END
            )
            FROM product_variants
            WHERE product_variants.product_id = products.id
                AND product_variants.is_active = 1)
        SQL;

        match ($sort) {
            'newest' => $query->latest('products.created_at'),
            'name' => $query->orderBy('products.name'),
            'price-asc' => $query->orderByRaw("{$currentPriceSql} ASC"),
            'price-desc' => $query->orderByRaw("{$currentPriceSql} DESC"),
            default => $query->ordered(),
        };
    }
}
