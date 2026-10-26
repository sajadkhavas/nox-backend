<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_contract_reports_public_v1_baseline(): void
    {
        $this->getJson('/api/system/contracts')->assertOk()
            ->assertJsonPath('data.contracts.catalog.status', 'public-v1-ready')
            ->assertJsonPath('data.contracts.catalog.source', 'f14-be-d');
    }

    public function test_catalog_calculates_prices_stock_and_content_boundaries(): void
    {
        $category = Category::query()->create([
            'name' => 'دسته آزمایشی',
            'slug' => 'test-category',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'محصول آزمایشی',
            'slug' => 'test-product',
            'product_code' => 'LBB-TEST-001',
            'description' => 'توضیح داخلی',
            'content_verified' => false,
            'is_featured' => true,
            'is_active' => true,
        ]);
        $first = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'name' => 'انتخاب اول',
            'sku' => 'LBB-TEST-001-A',
            'regular_price_toman' => 150000,
            'sale_price_toman' => 120000,
            'stock_quantity' => 3,
            'is_default' => true,
            'is_active' => true,
        ]);
        ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'name' => 'انتخاب دوم',
            'sku' => 'LBB-TEST-001-B',
            'regular_price_toman' => 240000,
            'stock_quantity' => 2,
            'is_active' => true,
        ]);

        $this->getJson('/api/catalog/products?category=test-category&featured=1&inStock=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.priceToman', 120000)
            ->assertJsonPath('data.0.stock', 5)
            ->assertJsonPath('data.0.longDescription', null)
            ->assertJsonPath('data.0.variants.0.id', $first->public_id)
            ->assertJsonPath('meta.pagination.total', 1);

        $product->update(['content_verified' => true]);
        $this->getJson('/api/catalog/products/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('data.longDescription', 'توضیح داخلی')
            ->assertJsonCount(2, 'data.variants');
    }

    public function test_inactive_and_out_of_stock_products_are_filtered(): void
    {
        $category = Category::query()->create([
            'name' => 'دسته دوم',
            'slug' => 'second-category',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'بدون موجودی',
            'slug' => 'out-of-stock',
            'product_code' => 'LBB-TEST-EMPTY',
            'is_active' => true,
        ]);
        ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'name' => 'استاندارد',
            'sku' => 'LBB-TEST-EMPTY-A',
            'regular_price_toman' => 100000,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $this->getJson('/api/catalog/products?inStock=1')->assertOk()->assertJsonCount(0, 'data');
    }
}
