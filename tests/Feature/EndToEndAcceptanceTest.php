<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_neutral_catalog_checkout_and_idempotency_work_together(): void
    {
        config([
            'lbb.checkout.enabled' => true,
            'lbb.checkout.delivery_methods.standard' => ['enabled' => true, 'fee_toman' => 0],
            'lbb.checkout.delivery_methods.pickup' => ['enabled' => true, 'fee_toman' => 0],
        ]);

        $customer = Customer::query()->create([
            'mobile' => '09000000000',
            'full_name' => 'مشتری آزمایشی',
            'mobile_verified_at' => now(),
            'is_active' => true,
        ]);
        $category = Category::query()->create([
            'name' => 'دسته پذیرش',
            'slug' => 'acceptance-category',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'محصول پذیرش',
            'slug' => 'acceptance-product',
            'product_code' => 'LBB-ACC-001',
            'content_verified' => true,
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'name' => 'انتخاب پذیرش',
            'sku' => 'LBB-ACC-001-A',
            'regular_price_toman' => 100000,
            'stock_quantity' => 5,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->getJson('/api/system/contracts')->assertOk()
            ->assertJsonPath('meta.contractVersion', '2026-09-06-p3-storefront-v1')
            ->assertJsonPath('data.contracts.catalog.status', 'public-v1-ready')
            ->assertJsonPath('data.contracts.authentication.status', 'public-v1-ready')
            ->assertJsonPath('data.contracts.storefront_content.status', 'public-v1-ready')
            ->assertJsonPath('data.contracts.backend_freeze.status', 'ready')
            ->assertJsonPath('data.launch.backend_complete', true)
            ->assertJsonPath('data.launch.frontend_integrated', true)
            ->assertJsonPath('data.launch.production_deployed', false);

        $payload = [
            'customer' => [
                'fullName' => 'مشتری آزمایشی',
                'mobile' => '09000000000',
                'province' => 'استان آزمایشی',
                'city' => 'شهر آزمایشی',
                'address' => 'نشانی صرفاً آزمایشی',
            ],
            'deliveryMethod' => 'standard',
            'items' => [['variantId' => $variant->public_id, 'quantity' => 2]],
        ];

        $first = $this->actingAs($customer, 'customer')->postJson('/api/checkout', $payload, [
            'Idempotency-Key' => 'f14-be-b2-acceptance-0001',
        ])->assertCreated()->assertJsonPath('meta.replayed', false);

        $this->actingAs($customer, 'customer')->postJson('/api/checkout', $payload, [
            'Idempotency-Key' => 'f14-be-b2-acceptance-0001',
        ])->assertOk()
            ->assertJsonPath('data.order.id', $first->json('data.order.id'))
            ->assertJsonPath('meta.replayed', true);
    }
}
