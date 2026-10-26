<?php

namespace App\Domain\Apparel;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VariantMatrixService
{
    public function apply(
        Product $product,
        array $colorIds,
        array $sizeIds,
        string $skuPrefix,
        int $regularPriceToman,
        int $stockQuantity = 0,
    ): array {
        $skuPrefix = Str::upper(trim($skuPrefix));

        if ($skuPrefix === '') {
            throw new \DomainException('An explicit SKU prefix is required.');
        }

        if ($regularPriceToman < 1 || $stockQuantity < 0) {
            throw new \DomainException('Matrix price and stock values are invalid.');
        }

        $colors = Color::query()->whereKey($colorIds)->active()->ordered()->get();
        $sizes = Size::query()->whereKey($sizeIds)->active()->ordered()->get();

        if ($colors->count() !== count(array_unique(array_map('intval', $colorIds)))) {
            throw new \DomainException('Every selected matrix color must exist and be active.');
        }

        if ($sizes->count() !== count(array_unique(array_map('intval', $sizeIds)))) {
            throw new \DomainException('Every selected matrix size must exist and be active.');
        }

        $candidates = [];

        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                $sku = $this->sku($skuPrefix, $color->code, $size->code);
                $candidates[] = compact('color', 'size', 'sku');
            }
        }

        $skus = array_column($candidates, 'sku');

        if (count($skus) !== count(array_unique($skus))) {
            throw new \DomainException('The selected matrix produces duplicate SKUs.');
        }

        $existingSkuOwners = ProductVariant::withTrashed()
            ->whereIn('sku', $skus)
            ->get()
            ->keyBy('sku');

        foreach ($candidates as $candidate) {
            $owner = $existingSkuOwners->get($candidate['sku']);

            if (
                $owner !== null
                && (
                    (int) $owner->product_id !== (int) $product->getKey()
                    || (int) $owner->color_id !== (int) $candidate['color']->getKey()
                    || (int) $owner->size_id !== (int) $candidate['size']->getKey()
                )
            ) {
                throw new \DomainException("SKU collision detected for [{$candidate['sku']}].");
            }
        }

        return DB::transaction(function () use (
            $product,
            $candidates,
            $regularPriceToman,
            $stockQuantity,
        ): array {
            $result = [
                'created' => 0,
                'existing' => 0,
                'skipped_deleted' => 0,
            ];

            foreach ($candidates as $candidate) {
                $existing = ProductVariant::withTrashed()
                    ->where('product_id', $product->getKey())
                    ->where('color_id', $candidate['color']->getKey())
                    ->where('size_id', $candidate['size']->getKey())
                    ->first();

                if ($existing !== null) {
                    $existing->trashed()
                        ? $result['skipped_deleted']++
                        : $result['existing']++;

                    continue;
                }

                ProductVariant::create([
                    'product_id' => $product->getKey(),
                    'color_id' => $candidate['color']->getKey(),
                    'size_id' => $candidate['size']->getKey(),
                    'name' => "{$candidate['color']->name} / {$candidate['size']->name}",
                    'sku' => $candidate['sku'],
                    'regular_price_toman' => $regularPriceToman,
                    'sale_price_toman' => null,
                    'stock_quantity' => $stockQuantity,
                    'low_stock_threshold' => 5,
                    'is_default' => false,
                    'is_active' => false,
                    'sort_order' => 0,
                ]);

                $result['created']++;
            }

            return $result;
        });
    }

    private function sku(string $prefix, string $colorCode, string $sizeCode): string
    {
        $parts = array_map(
            static fn (string $part): string => Str::upper(
                preg_replace('/[^A-Za-z0-9]+/', '-', trim($part)) ?? '',
            ),
            [$prefix, $colorCode, $sizeCode],
        );

        $sku = trim(implode('-', array_filter($parts)), '-');

        if ($sku === '' || mb_strlen($sku) > 100) {
            throw new \DomainException('Generated SKU is empty or exceeds 100 characters.');
        }

        return $sku;
    }
}
