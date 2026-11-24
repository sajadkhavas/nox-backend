<?php
namespace Tests\Feature;

use App\Enums\PublicationStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use Database\Seeders\NoxShopCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NoxShopCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_setup_preserves_products_and_variants_while_normalizing_categories(): void
    {
        Storage::fake('public');
        // The established connection remains an isolated in-memory SQLite database.
        DB::connection()->setDatabaseName('nox');
        $legacy = Category::query()->create(['name'=>'جین','slug'=>'denim','is_active'=>true]);
        $product = Product::query()->create(['category_id'=>$legacy->id,'name'=>'محصول آزمایشی','slug'=>'test-jean','product_code'=>'TEST-JEAN','publication_status'=>PublicationStatus::Draft,'is_active'=>false]);
        $variant = ProductVariant::query()->create(['product_id'=>$product->id,'name'=>'L','sku'=>'TEST-L','regular_price_toman'=>123456,'stock_quantity'=>7,'is_default'=>true,'is_active'=>true]);
        $beforeVariant = $variant->getRawOriginal();
        $this->seed(NoxShopCatalogSeeder::class);
        $this->assertDatabaseCount('products', 1);
        $this->assertSame($beforeVariant, $variant->fresh()->getRawOriginal());
        $this->assertSame(PublicationStatus::Draft, $product->fresh()->publication_status);
        $this->assertFalse($product->fresh()->is_active);
        $this->assertSame('pants', $product->fresh()->category->slug);
        $this->assertNotNull(Category::find($legacy->id));
        $this->assertFalse($legacy->fresh()->is_active);
        $response = $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(6,'data');
        $pants = collect($response->json('data'))->firstWhere('slug','pants');
        $this->assertSame('/shop/pants', $pants['seo']['canonicalPath']);
        $bootstrap = $this->getJson('/api/v1/storefront/bootstrap')->assertOk();
        $home = $bootstrap->json('data.settings.home');
        $this->assertStringContainsString('/storage/', $home['home.nox_visuals']['shop']);
        $this->assertCount(6, $home['home.nox_catalog']['categories']);
        $this->assertCount(3, $home['home.nox_catalog']['categories']['pants']['sections']);
        // Rerunning updates neither uploaded media count nor product commerce data.
        $before = DB::table('media')->count();
        $this->seed(NoxShopCatalogSeeder::class);
        $this->assertSame($before, DB::table('media')->count());
        $this->assertSame($beforeVariant, $variant->fresh()->getRawOriginal());
    }

    public function test_catalog_guide_photo_is_hydrated_and_content_remains_plain_text(): void
    {
        StoreSetting::query()->create(['key'=>'home.nox_catalog','group'=>'home','type'=>'json','is_public'=>true,'value'=>json_encode(['shopTitle'=>'فروشگاه ناکس','categories'=>['pants'=>['intro'=>'معرفی کوتاه','title'=>'راهنمای شلوار','image'=>'nox/pants.webp','sections'=>[['heading'=>'انتخاب سایز','text'=>'اندازه‌ها را بررسی کن.']]]]])]);
        $home = $this->getJson('/api/v1/storefront/bootstrap')->assertOk()->json('data.settings.home');
        $this->assertStringEndsWith('/storage/nox/pants.webp', $home['home.nox_catalog']['categories']['pants']['image']);
        $this->assertSame('اندازه‌ها را بررسی کن.', $home['home.nox_catalog']['categories']['pants']['sections'][0]['text']);
    }
}
