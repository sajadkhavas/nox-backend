<?php

namespace Tests\Feature;

use App\Enums\EvidenceState;
use App\Enums\ProductFact;
use App\Enums\PublicationStatus;
use App\Models\Category;
use App\Models\Collection as ApparelCollection;
use App\Models\Color;
use App\Models\MeasurementDefinition;
use App\Models\Product;
use App\Models\ProductEvidence;
use App\Models\ProductMediaAsset;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\SizeGuide;
use App\Models\SizeGuideMeasurement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicCommerceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_categories_only_expose_active_published_categories(): void
    {
        $fixture = $this->publishedProduct('category-public');

        Category::create([
            'name' => 'Draft category',
            'slug' => 'draft-category',
            'publication_status' => PublicationStatus::Draft,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.publicId', $fixture['category']->public_id)
            ->assertJsonMissing(['id' => $fixture['category']->id]);
    }

    public function test_product_listing_hides_draft_and_archived_products_and_has_stable_pagination_contract(): void
    {
        $published = $this->publishedProduct('public-product');
        $this->draftProduct('draft-product');
        $archived = $this->publishedProduct('archived-product');
        $archived['product']->update(['publication_status' => PublicationStatus::Archived]);

        $this->getJson('/api/v1/products?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.publicId', $published['product']->public_id)
            ->assertJsonPath('meta.pagination.page', 1)
            ->assertJsonPath('meta.pagination.perPage', 1)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonStructure([
                'success',
                'data' => [[
                    'publicId',
                    'slug',
                    'name',
                    'price' => ['from' => ['amount', 'currency'], 'to' => ['amount', 'currency']],
                    'availability',
                    'stockState',
                    'colors',
                    'sizes',
                    'seo',
                ]],
                'meta' => [
                    'requestId',
                    'apiVersion',
                    'contractVersion',
                    'pagination' => ['page', 'perPage', 'total', 'totalPages', 'from', 'to', 'hasMore'],
                    'links' => ['self', 'next', 'previous'],
                ],
            ]);
    }

    public function test_product_detail_is_frontend_ready_and_does_not_leak_internal_ids_or_stock_quantity(): void
    {
        $fixture = $this->publishedProduct('detail', salePrice: 85000);

        $response = $this->getJson('/api/v1/products/'.$fixture['product']->slug)
            ->assertOk()
            ->assertJsonPath('data.publicId', $fixture['product']->public_id)
            ->assertJsonPath('data.variants.0.publicId', $fixture['variant']->public_id)
            ->assertJsonPath('data.variants.0.price.amount', 85000)
            ->assertJsonPath('data.variants.0.compareAtPrice.amount', 100000)
            ->assertJsonPath('data.variants.0.price.currency', 'TOMAN')
            ->assertJsonPath('data.variants.0.stockState', 'in_stock')
            ->assertJsonPath('data.publication', 'published');

        $payload = $response->json('data');

        $this->assertArrayNotHasKey('id', $payload);
        $this->assertArrayNotHasKey('stock', $payload['variants'][0]);
        $this->assertArrayNotHasKey('stock_quantity', $payload['variants'][0]);
        $this->assertArrayNotHasKey('available_quantity', $payload['variants'][0]);
    }

    public function test_invalid_slug_uses_consistent_not_found_error_contract(): void
    {
        $this->getJson('/api/v1/products/missing-product')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'resource_not_found')
            ->assertJsonStructure(['success', 'code', 'message', 'errors', 'meta' => ['requestId', 'apiVersion']]);
    }

    public function test_inactive_variant_is_hidden_and_does_not_participate_in_variant_matrix(): void
    {
        $fixture = $this->publishedProduct('inactive-variant');

        $secondSize = Size::create([
            'name' => 'Large',
            'code' => 'L-INACTIVE',
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $fixture['product']->id,
            'color_id' => $fixture['color']->id,
            'size_id' => $secondSize->id,
            'name' => 'Inactive',
            'sku' => 'INACTIVE-SKU',
            'regular_price_toman' => 120000,
            'stock_quantity' => 99,
            'is_active' => false,
        ]);

        $this->getJson('/api/v1/products/'.$fixture['product']->slug)
            ->assertOk()
            ->assertJsonCount(1, 'data.variants')
            ->assertJsonMissing(['sku' => 'INACTIVE-SKU']);
    }

    public function test_pending_optional_evidence_never_leaks_as_public_fact(): void
    {
        $fixture = $this->publishedProduct('evidence');

        DB::table('products')->where('id', $fixture['product']->id)->update([
            'material' => 'Unverified internal material',
        ]);

        ProductEvidence::create([
            'product_id' => $fixture['product']->id,
            'fact_key' => ProductFact::Material->value,
            'state' => EvidenceState::Pending,
            'source_reference' => 'internal://pending-material',
        ]);

        $this->getJson('/api/v1/products/'.$fixture['product']->slug)
            ->assertOk()
            ->assertJsonPath('data.material', null)
            ->assertJsonPath('data.fabricComposition', null)
            ->assertJsonMissing(['source_reference' => 'internal://pending-material']);
    }

    public function test_filters_search_sort_and_facets_are_server_side_and_whitelisted(): void
    {
        $cheap = $this->publishedProduct('alpha-shirt', price: 70000);
        $expensive = $this->publishedProduct('beta-shirt', price: 170000);

        $this->getJson('/api/v1/search?q=alpha')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.publicId', $cheap['product']->public_id);

        $this->getJson('/api/v1/products?sort=price_desc')
            ->assertOk()
            ->assertJsonPath('data.0.publicId', $expensive['product']->public_id);

        $this->getJson('/api/v1/products?min_price=100000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.publicId', $expensive['product']->public_id);

        $this->getJson('/api/v1/products?sort=drop_table')
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_failed');

        $this->getJson('/api/v1/catalog/facets')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['categories', 'collections', 'colors', 'sizes', 'price' => ['min', 'max'], 'availability', 'sorts'],
            ])
            ->assertJsonPath('data.price.min.currency', 'TOMAN')
            ->assertJsonPath('data.price.max.amount', 170000);
    }

    public function test_combined_catalog_filters_match_one_buyable_variant(): void
    {
        $fixture = $this->publishedProduct('variant-pair', price: 100000);

        $otherColor = Color::create([
            'name' => 'Other color',
            'slug' => 'other-color',
            'code' => 'OTHER-COLOR',
            'is_active' => true,
        ]);
        $otherSize = Size::create([
            'name' => 'Other size',
            'code' => 'XL',
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $fixture['product']->id,
            'color_id' => $otherColor->id,
            'size_id' => $otherSize->id,
            'name' => 'Other combination',
            'sku' => 'SKU-OTHER-COMBINATION',
            'regular_price_toman' => 200000,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/products?color='.$fixture['color']->slug.'&size=XL')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/products?color=other-color&size=XL&availability=in_stock')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/products?color=other-color&size=XL&max_price=150000')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/products?color=other-color&size=XL&max_price=250000')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1);

        $this->getJson('/api/v1/products?max_price=100000')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1);

        $this->getJson('/api/v1/products?min_price=200000&max_price=100000')
            ->assertStatus(422)
            ->assertJsonValidationErrors('max_price');
    }

    public function test_availability_filter_is_server_authoritative_and_exact_quantity_remains_private(): void
    {
        $fixture = $this->publishedProduct('availability', stock: 0);

        $this->getJson('/api/v1/products/'.$fixture['product']->slug)
            ->assertOk()
            ->assertJsonPath('data.variants.0.availability', false)
            ->assertJsonPath('data.variants.0.stockState', 'out_of_stock');

        $this->getJson('/api/v1/products?availability=in_stock')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/products?availability=out_of_stock')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_collections_require_published_collection_and_verified_membership(): void
    {
        $fixture = $this->publishedProduct('collection');

        $collection = ApparelCollection::create([
            'name' => 'Public collection',
            'slug' => 'public-collection',
            'publication_status' => PublicationStatus::Published,
        ]);

        $collection->products()->attach($fixture['product']->id);

        $this->getJson('/api/v1/products/'.$fixture['product']->slug)
            ->assertOk()
            ->assertJsonCount(0, 'data.collections');

        ProductEvidence::create([
            'product_id' => $fixture['product']->id,
            'fact_key' => ProductFact::CollectionMembership->value,
            'state' => EvidenceState::Verified,
            'source_reference' => 'internal://collection-proof',
        ]);

        $this->getJson('/api/v1/collections/public-collection')
            ->assertOk()
            ->assertJsonPath('data.collection.publicId', $collection->public_id)
            ->assertJsonCount(1, 'data.products');

        $this->getJson('/api/v1/products/'.$fixture['product']->slug)
            ->assertOk()
            ->assertJsonPath('data.collections.0.publicId', $collection->public_id);
    }

    public function test_size_guide_is_extensible_and_requires_verified_evidence(): void
    {
        $fixture = $this->publishedProduct('size-guide');

        $guide = SizeGuide::create([
            'name' => 'Tops guide',
            'description' => 'Verified guide',
            'unit' => 'cm',
            'is_active' => true,
        ]);

        $definition = MeasurementDefinition::create([
            'code' => 'chest-flat',
            'label' => 'Chest flat',
            'is_active' => true,
        ]);

        SizeGuideMeasurement::create([
            'size_guide_id' => $guide->id,
            'size_id' => $fixture['size']->id,
            'measurement_definition_id' => $definition->id,
            'value' => 54.50,
        ]);

        $fixture['product']->update(['size_guide_id' => $guide->id]);

        $this->getJson('/api/v1/products/'.$fixture['product']->slug)
            ->assertOk()
            ->assertJsonPath('data.sizeGuide', null);

        ProductEvidence::create([
            'product_id' => $fixture['product']->id,
            'fact_key' => ProductFact::SizeGuide->value,
            'state' => EvidenceState::Verified,
            'source_reference' => 'internal://size-guide-proof',
        ]);

        $this->getJson('/api/v1/products/'.$fixture['product']->slug)
            ->assertOk()
            ->assertJsonPath('data.sizeGuide.publicId', $guide->public_id)
            ->assertJsonPath('data.sizeGuide.unit', 'cm')
            ->assertJsonPath('data.sizeGuide.definitions.0.code', 'chest-flat')
            ->assertJsonPath('data.sizeGuide.sizes.0.measurements.0.value', '54.50');
    }

    public function test_seo_dto_contains_source_fields_not_html_meta_markup(): void
    {
        $fixture = $this->publishedProduct('seo');

        $fixture['product']->update([
            'meta_title' => 'SEO title',
            'meta_description' => 'SEO description',
        ]);

        $response = $this->getJson('/api/v1/products/'.$fixture['product']->slug)
            ->assertOk()
            ->assertJsonPath('data.seo.metaTitle', 'SEO title')
            ->assertJsonPath('data.seo.canonicalPath', '/product/'.$fixture['product']->slug)
            ->assertJsonPath('data.seo.publication', 'published')
            ->assertJsonStructure(['data' => ['seo' => ['structuredData', 'breadcrumbs']]]);

        $this->assertStringNotContainsString('<meta', json_encode($response->json('data.seo')));
    }

    public function test_catalog_query_count_stays_bounded_as_result_count_grows(): void
    {
        foreach (range(1, 5) as $index) {
            $this->publishedProduct('query-'.$index);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/v1/products?per_page=5')->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(30, $queryCount, "Catalog query count unexpectedly grew to {$queryCount}.");
    }

    public function test_search_route_has_a_dedicated_rate_limit(): void
    {
        $this->publishedProduct('rate-limit');

        foreach (range(1, 60) as $attempt) {
            $this->getJson('/api/v1/search?q=rate')->assertOk();
        }

        $this->getJson('/api/v1/search?q=rate')
            ->assertStatus(429)
            ->assertJsonPath('code', 'rate_limited');
    }

    private function publishedProduct(
        string $suffix,
        int $price = 100000,
        ?int $salePrice = null,
        int $stock = 10,
    ): array {
        $category = Category::create([
            'name' => 'Category '.$suffix,
            'slug' => 'category-'.$suffix,
            'publication_status' => PublicationStatus::Published,
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Product '.$suffix,
            'slug' => 'product-'.$suffix,
            'product_code' => 'P-'.strtoupper($suffix),
            'publication_status' => PublicationStatus::Draft,
            'is_active' => true,
        ]);

        $color = Color::create([
            'name' => 'Color '.$suffix,
            'slug' => 'color-'.$suffix,
            'code' => 'C-'.strtoupper($suffix),
            'is_active' => true,
        ]);

        $size = Size::create([
            'name' => 'Size '.$suffix,
            'code' => 'S-'.strtoupper($suffix),
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'size_id' => $size->id,
            'name' => 'Default',
            'sku' => 'SKU-'.strtoupper($suffix),
            'regular_price_toman' => $price,
            'sale_price_toman' => $salePrice,
            'stock_quantity' => $stock,
            'low_stock_threshold' => 3,
            'is_default' => true,
            'is_active' => true,
        ]);

        ProductMediaAsset::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'variant_id' => $variant->id,
            'role' => 'primary',
            'alt_text' => 'Product '.$suffix,
            'verification_state' => EvidenceState::Verified,
        ]);

        $facts = [
            ProductFact::Name,
            ProductFact::Media,
            ProductFact::Price,
            ProductFact::Colors,
            ProductFact::Sizes,
            ProductFact::Stock,
            ProductFact::Sku,
        ];

        if ($salePrice !== null) {
            $facts[] = ProductFact::PreviousPrice;
        }

        foreach ($facts as $fact) {
            ProductEvidence::create([
                'product_id' => $product->id,
                'fact_key' => $fact->value,
                'state' => EvidenceState::Verified,
                'source_reference' => 'internal://'.$suffix.'/'.$fact->value,
            ]);
        }

        $product->update(['publication_status' => PublicationStatus::Published]);

        return compact('category', 'product', 'color', 'size', 'variant');
    }

    private function draftProduct(string $suffix): Product
    {
        $category = Category::create([
            'name' => 'Draft category '.$suffix,
            'slug' => 'draft-category-'.$suffix,
            'publication_status' => PublicationStatus::Published,
            'is_active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Draft '.$suffix,
            'slug' => 'draft-'.$suffix,
            'product_code' => 'DRAFT-'.strtoupper($suffix),
            'publication_status' => PublicationStatus::Draft,
            'is_active' => true,
        ]);
    }
}
