<?php

namespace Tests\Feature;

use App\Domain\Apparel\VariantMatrixService;
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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApparelDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_color_and_size_entities_are_reusable_and_keep_name_separate_from_code(): void
    {
        $color = Color::create([
            'name' => 'Display color',
            'code' => 'CLR-001',
            'hex' => null,
            'is_active' => true,
        ]);

        $size = Size::create([
            'name' => 'Display size',
            'code' => 'SIZE-001',
            'is_active' => true,
        ]);

        $this->assertNotSame($color->name, $color->code);
        $this->assertNull($color->hex);
        $this->assertTrue($color->is_active);
        $this->assertTrue($size->is_active);
        $this->assertNotEmpty($color->public_id);
        $this->assertNotEmpty($size->public_id);
    }

    public function test_variant_sku_is_unique_and_color_size_combination_is_unique_per_product(): void
    {
        [$product, $color, $size] = $this->apparelFixture();

        ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'size_id' => $size->id,
            'name' => 'Variant A',
            'sku' => 'SKU-UNIQUE-A',
            'regular_price_toman' => 100000,
            'stock_quantity' => 4,
            'is_active' => true,
        ]);

        try {
            ProductVariant::create([
                'product_id' => $product->id,
                'color_id' => $color->id,
                'size_id' => $size->id,
                'name' => 'Variant B',
                'sku' => 'SKU-UNIQUE-B',
                'regular_price_toman' => 100000,
                'stock_quantity' => 1,
                'is_active' => true,
            ]);

            $this->fail('Duplicate color-size combination was accepted.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $otherSize = Size::create(['name' => 'Other', 'code' => 'OTHER', 'is_active' => true]);

        $this->expectException(QueryException::class);

        ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'size_id' => $otherSize->id,
            'name' => 'Variant C',
            'sku' => 'SKU-UNIQUE-A',
            'regular_price_toman' => 100000,
            'stock_quantity' => 1,
            'is_active' => true,
        ]);
    }

    public function test_variant_stock_is_server_calculated_and_inactive_color_or_size_is_not_sellable(): void
    {
        [$product, $color, $size] = $this->apparelFixture();

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'size_id' => $size->id,
            'name' => 'Stocked',
            'sku' => 'STOCK-001',
            'regular_price_toman' => 100000,
            'stock_quantity' => 4,
            'low_stock_threshold' => 5,
            'is_active' => true,
        ]);

        $this->assertSame(4, $variant->stock_on_hand);
        $this->assertSame(0, $variant->reserved_quantity);
        $this->assertSame(4, $variant->available_quantity);
        $this->assertTrue($variant->is_sellable);
        $this->assertTrue($variant->low_stock);

        $color->update(['is_active' => false]);

        $this->assertFalse($variant->fresh()->is_sellable);
        $this->assertFalse(ProductVariant::query()->sellable()->whereKey($variant->id)->exists());
    }

    public function test_product_variant_collection_size_guide_and_measurement_relationships(): void
    {
        [$product, $color, $size] = $this->apparelFixture();

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'size_id' => $size->id,
            'name' => 'Rel',
            'sku' => 'REL-001',
            'regular_price_toman' => 100000,
            'stock_quantity' => 2,
            'is_active' => false,
        ]);

        $collection = ApparelCollection::create([
            'name' => 'Editorial collection',
            'publication_status' => PublicationStatus::Draft,
        ]);
        $collection->products()->attach($product->id, ['sort_order' => 7]);

        $guide = SizeGuide::create([
            'name' => 'Guide',
            'unit' => 'cm',
            'is_active' => true,
        ]);
        $definition = MeasurementDefinition::create([
            'code' => 'dimension-001',
            'label' => 'Dimension 001',
            'is_active' => true,
        ]);

        SizeGuideMeasurement::create([
            'size_guide_id' => $guide->id,
            'size_id' => $size->id,
            'measurement_definition_id' => $definition->id,
            'value' => 55.50,
        ]);

        $product->update(['size_guide_id' => $guide->id]);

        $this->assertTrue($product->fresh()->variants->contains($variant));
        $this->assertTrue($product->fresh()->collections->contains($collection));
        $this->assertSame('cm', $product->fresh()->sizeGuide->unit);
        $this->assertSame('55.50', $guide->fresh()->measurements->first()->value);
    }

    public function test_evidence_states_are_explicit_and_unverified_product_cannot_be_published(): void
    {
        [$product] = $this->apparelFixture();

        ProductEvidence::create([
            'product_id' => $product->id,
            'fact_key' => ProductFact::Name->value,
            'state' => EvidenceState::Pending,
            'source_reference' => 'source://pending-name',
        ]);

        $this->expectException(\DomainException::class);

        $product->publication_status = PublicationStatus::Published;
        $product->save();
    }

    public function test_product_can_publish_only_after_apparel_identity_media_and_required_evidence_are_verified(): void
    {
        [$product, $color, $size] = $this->apparelFixture();

        ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'size_id' => $size->id,
            'name' => 'Publishable',
            'sku' => 'PUB-001',
            'regular_price_toman' => 100000,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        ProductMediaAsset::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'role' => 'front',
            'verification_state' => EvidenceState::Verified,
            'sort_order' => 0,
        ]);

        foreach ([
            ProductFact::Name,
            ProductFact::Media,
            ProductFact::Price,
            ProductFact::Colors,
            ProductFact::Sizes,
            ProductFact::Stock,
            ProductFact::Sku,
        ] as $fact) {
            ProductEvidence::create([
                'product_id' => $product->id,
                'fact_key' => $fact->value,
                'state' => EvidenceState::Verified,
                'source_reference' => "source://{$fact->value}",
            ]);
        }

        $product->publication_status = PublicationStatus::Published;
        $product->save();

        $this->assertSame(PublicationStatus::Published, $product->fresh()->publication_status);
        $this->assertNotNull($product->fresh()->published_at);

        $this->expectException(\DomainException::class);
        $product->name = 'Changed after verification';
        $product->save();
    }

    public function test_media_variant_must_belong_to_same_product(): void
    {
        [$product, $color, $size] = $this->apparelFixture();
        [$otherProduct] = $this->apparelFixture('other');

        $variant = ProductVariant::create([
            'product_id' => $otherProduct->id,
            'color_id' => $color->id,
            'size_id' => $size->id,
            'name' => 'Other variant',
            'sku' => 'MEDIA-OTHER',
            'regular_price_toman' => 100000,
            'stock_quantity' => 0,
            'is_active' => false,
        ]);

        $this->expectException(\DomainException::class);

        ProductMediaAsset::create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'role' => 'detail',
            'verification_state' => EvidenceState::Pending,
        ]);
    }

    public function test_variant_matrix_preserves_existing_and_does_not_restore_deleted_combinations(): void
    {
        [$product, $color, $size] = $this->apparelFixture();

        $existing = ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'size_id' => $size->id,
            'name' => 'Existing',
            'sku' => 'MATRIX-COLOR-SIZE',
            'regular_price_toman' => 90000,
            'stock_quantity' => 9,
            'is_active' => false,
        ]);

        $result = app(VariantMatrixService::class)->apply(
            $product,
            [$color->id],
            [$size->id],
            'MATRIX',
            120000,
            2,
        );

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['existing']);
        $this->assertSame(90000, $existing->fresh()->regular_price_toman);
        $this->assertSame(9, $existing->fresh()->stock_quantity);

        $existing->delete();

        $result = app(VariantMatrixService::class)->apply(
            $product,
            [$color->id],
            [$size->id],
            'MATRIX',
            120000,
            2,
        );

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['skipped_deleted']);
        $this->assertTrue(ProductVariant::withTrashed()->findOrFail($existing->id)->trashed());
    }

    private function apparelFixture(string $suffix = 'base'): array
    {
        $category = Category::create([
            'name' => "Category {$suffix}",
            'slug' => "category-{$suffix}-".uniqid(),
            'publication_status' => PublicationStatus::Draft,
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => "Product {$suffix}",
            'slug' => "product-{$suffix}-".uniqid(),
            'product_code' => 'P-'.strtoupper($suffix).'-'.uniqid(),
            'publication_status' => PublicationStatus::Draft,
            'is_active' => false,
        ]);

        $color = Color::create([
            'name' => "Color {$suffix}",
            'slug' => "color-{$suffix}-".uniqid(),
            'code' => 'C-'.strtoupper($suffix).'-'.uniqid(),
            'is_active' => true,
        ]);

        $size = Size::create([
            'name' => "Size {$suffix}",
            'code' => 'S-'.strtoupper($suffix).'-'.uniqid(),
            'is_active' => true,
        ]);

        return [$product, $color, $size];
    }
}
