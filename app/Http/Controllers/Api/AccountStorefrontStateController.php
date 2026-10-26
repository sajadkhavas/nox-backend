<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AccountStorefrontStateController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->state($this->customer($request)));
    }

    public function replaceWishlist(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slugs' => ['present', 'array', 'max:100'],
            'slugs.*' => ['string', 'max:160', 'distinct'],
        ]);

        $customer = $this->customer($request);
        $slugs = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $validated['slugs'],
        ))));

        $products = Product::query()
            ->whereIn('slug', $slugs)
            ->where('is_active', true)
            ->get(['id']);

        DB::transaction(function () use ($customer, $products): void {
            DB::table('customer_wishlist_items')->where('customer_id', $customer->getKey())->delete();

            $now = now();
            $rows = $products->map(fn (Product $product): array => [
                'customer_id' => $customer->getKey(),
                'product_id' => $product->getKey(),
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows !== []) {
                DB::table('customer_wishlist_items')->insert($rows);
            }
        });

        return ApiResponse::success($this->state($customer));
    }

    public function replaceCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['present', 'array', 'max:50'],
            'items.*.variantId' => ['required', 'string', 'size:26', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $customer = $this->customer($request);
        $requested = collect($validated['items'])->keyBy('variantId');

        $variants = ProductVariant::query()
            ->sellable()
            ->whereIn('public_id', $requested->keys())
            ->whereHas('product', fn (Builder $product): Builder => $product
                ->published()
                ->where('is_active', true))
            ->get(['id', 'public_id']);

        DB::transaction(function () use ($customer, $variants, $requested): void {
            DB::table('customer_cart_items')->where('customer_id', $customer->getKey())->delete();

            $now = now();
            $rows = $variants->map(fn (ProductVariant $variant): array => [
                'customer_id' => $customer->getKey(),
                'product_variant_id' => $variant->getKey(),
                'quantity' => (int) $requested[$variant->public_id]['quantity'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows !== []) {
                DB::table('customer_cart_items')->insert($rows);
            }
        });

        return ApiResponse::success($this->state($customer));
    }

    private function customer(Request $request): Customer
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        return $customer;
    }

    private function state(Customer $customer): array
    {
        $wishlistProductIds = DB::table('customer_wishlist_items')
            ->where('customer_id', $customer->getKey())
            ->orderBy('id')
            ->pluck('product_id');

        $wishlistSlugs = Product::query()
            ->whereIn('id', $wishlistProductIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('slug')
            ->values()
            ->all();

        $cartRows = DB::table('customer_cart_items')
            ->where('customer_id', $customer->getKey())
            ->orderBy('id')
            ->get(['product_variant_id', 'quantity'])
            ->keyBy('product_variant_id');

        $cartItems = ProductVariant::query()
            ->with(['product:id,slug,name,is_active,deleted_at', 'color:id,name', 'size:id,name'])
            ->whereIn('id', $cartRows->keys())
            ->get()
            ->filter(fn (ProductVariant $variant): bool => $variant->product?->is_active === true && $variant->product?->deleted_at === null)
            ->map(function (ProductVariant $variant) use ($cartRows): array {
                $row = $cartRows[$variant->getKey()];

                return [
                    'variantId' => $variant->public_id,
                    'slug' => $variant->product->slug,
                    'name' => $variant->product->name,
                    'quantity' => (int) $row->quantity,
                    'priceToman' => $variant->current_price_toman,
                    'colorLabel' => $variant->color?->name,
                    'sizeLabel' => $variant->size?->name,
                ];
            })
            ->values()
            ->all();

        return [
            'wishlistSlugs' => $wishlistSlugs,
            'cartItems' => $cartItems,
        ];
    }
}
