<?php

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\PublicCatalogQuery;
use App\Domain\Catalog\PublicCatalogTransformer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CatalogIndexRequest;
use App\Http\Requests\Api\V1\CatalogSearchRequest;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Drop;
use App\Models\Size;
use App\Support\ApiResponse;
use App\Support\PublicMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

final class PublicCatalogController extends Controller
{
    public function __construct(
        private readonly PublicCatalogQuery $catalog,
        private readonly PublicCatalogTransformer $transformer,
    ) {}

    public function products(CatalogIndexRequest $request): JsonResponse
    {
        return $this->productPage($this->catalog->products($request->validated()));
    }

    public function search(CatalogSearchRequest $request): JsonResponse
    {
        return $this->productPage($this->catalog->products($request->validated()));
    }

    public function product(string $slug): JsonResponse
    {
        return ApiResponse::success(
            $this->transformer->productDetail($this->catalog->product($slug)),
        );
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->published()
            ->active()
            ->ordered()
            ->get();

        return ApiResponse::success(
            $categories->map(fn (Category $category): array => $this->transformer->category($category))->all(),
        );
    }

    public function category(string $slug): JsonResponse
    {
        $category = Category::query()
            ->published()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $count = $this->catalog->publishedProducts()
            ->where('category_id', $category->id)
            ->count();

        return ApiResponse::success($this->transformer->category($category, $count));
    }

    public function collections(): JsonResponse
    {
        $collections = Collection::query()
            ->published()
            ->ordered()
            ->get();

        return ApiResponse::success(
            $collections->map(fn (Collection $collection): array => $this->collectionPayload($collection))->all(),
        );
    }

    public function collection(CatalogIndexRequest $request, string $slug): JsonResponse
    {
        [$collection, $products] = $this->catalog->productsForCollection($slug, $request->validated());

        return ApiResponse::success(
            [
                'collection' => $this->collectionPayload($collection, $products->total()),
                'products' => $products->getCollection()
                    ->map(fn ($product): array => $this->transformer->productSummary($product))
                    ->all(),
            ],
            meta: $this->paginationMeta($products),
        );
    }

    public function drops(): JsonResponse
    {
        $drops = Drop::query()
            ->published()
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->ordered()
            ->get();

        return ApiResponse::success(
            $drops->map(fn (Drop $drop): array => $this->transformer->drop($drop))->all(),
        );
    }

    public function drop(string $slug): JsonResponse
    {
        $drop = Drop::query()
            ->published()
            ->where('slug', $slug)
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->firstOrFail();

        return ApiResponse::success($this->transformer->drop($drop));
    }

    public function colors(): JsonResponse
    {
        $published = $this->catalog->publishedProducts()->select('products.id');

        $colors = Color::query()
            ->active()
            ->whereHas('variants', fn (Builder $variants): Builder => $variants
                ->sellable()
                ->whereIn('product_id', clone $published))
            ->ordered()
            ->get();

        return ApiResponse::success(
            $colors->map(fn (Color $color): array => $this->transformer->color($color))->all(),
        );
    }

    public function sizes(): JsonResponse
    {
        $published = $this->catalog->publishedProducts()->select('products.id');

        $sizes = Size::query()
            ->active()
            ->whereHas('variants', fn (Builder $variants): Builder => $variants
                ->sellable()
                ->whereIn('product_id', clone $published))
            ->ordered()
            ->get();

        return ApiResponse::success(
            $sizes->map(fn (Size $size): array => $this->transformer->size($size))->all(),
        );
    }

    public function facets(): JsonResponse
    {
        $facets = $this->catalog->facets();

        return ApiResponse::success([
            'categories' => $facets['categories']->map(
                fn (Category $category): array => $this->transformer->category($category),
            )->all(),
            'collections' => $facets['collections']->map(
                fn (Collection $collection): array => $this->collectionPayload($collection),
            )->all(),
            'colors' => $facets['colors']->map(
                fn (Color $color): array => $this->transformer->color($color),
            )->all(),
            'sizes' => $facets['sizes']->map(
                fn (Size $size): array => $this->transformer->size($size),
            )->all(),
            'price' => [
                'min' => $facets['price']['min'] === null ? null : [
                    'amount' => $facets['price']['min'],
                    'currency' => 'TOMAN',
                ],
                'max' => $facets['price']['max'] === null ? null : [
                    'amount' => $facets['price']['max'],
                    'currency' => 'TOMAN',
                ],
            ],
            'availability' => $facets['availability'],
            'sorts' => $facets['sorts'],
        ]);
    }

    private function productPage(LengthAwarePaginator $products): JsonResponse
    {
        return ApiResponse::success(
            $products->getCollection()
                ->map(fn ($product): array => $this->transformer->productSummary($product))
                ->all(),
            meta: $this->paginationMeta($products),
        );
    }

    private function collectionPayload(Collection $collection, ?int $productCount = null): array
    {
        $payload = $this->transformer->collection($collection, $productCount);
        $cover = PublicMediaUrl::fromPathOrUrl($collection->cover_image_path);

        if ($cover !== null) {
            $payload['coverImage'] = $cover;
            $payload['seo']['primaryImage'] = $cover;
        }

        return $payload;
    }

    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'pagination' => [
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'totalPages' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'hasMore' => $paginator->hasMorePages(),
            ],
            'links' => [
                'self' => $paginator->url($paginator->currentPage()),
                'next' => $paginator->nextPageUrl(),
                'previous' => $paginator->previousPageUrl(),
            ],
        ];
    }
}
