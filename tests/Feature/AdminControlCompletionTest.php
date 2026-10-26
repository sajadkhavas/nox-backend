<?php

namespace Tests\Feature;

use App\Filament\Pages\FileManagerPage;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminControlCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_control_defaults_are_exposed_without_enabling_commerce(): void
    {
        $response = $this->getJson('/api/v1/storefront/bootstrap')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.runtime.checkoutEnabled', false)
            ->assertJsonPath('data.runtime.payment.enabled', false)
            ->assertJsonPath('data.runtime.payment.provider', 'disabled');

        $home = $response->json('data.settings.home');
        $shell = $response->json('data.settings.shell');
        $page = $response->json('data.settings.page');
        $faq = $response->json('data.settings.faq');
        $announcement = $response->json('data.settings.announcement');
        $navigation = $response->json('data.settings.navigation');

        $this->assertIsArray($home);
        $this->assertIsArray($shell);
        $this->assertIsArray($page);
        $this->assertIsArray($faq);
        $this->assertIsArray($announcement);
        $this->assertIsArray($navigation);

        $this->assertTrue($home['home.presentation']['heroEnabled']);
        $this->assertSame('newest', $home['home.presentation']['productCuration']['mode']);
        $this->assertSame(4, $home['home.presentation']['productCuration']['count']);
        $this->assertSame('فروشگاه', $shell['shell.copy']['shopMenuLabel']);
        $this->assertIsArray($page['page.presentation']);
        $this->assertIsArray($faq['faq.presentation']);
        $this->assertIsArray($announcement['announcement.messages']);
        $this->assertIsArray($navigation['navigation.shop']);
    }

    public function test_disabled_announcement_and_navigation_rows_are_not_exposed_publicly(): void
    {
        StoreSetting::query()
            ->where('key', 'announcement.messages')
            ->firstOrFail()
            ->update([
                'value' => json_encode([
                    ['text' => 'VISIBLE', 'href' => '/shop', 'enabled' => true],
                    ['text' => 'HIDDEN', 'href' => '/contact', 'enabled' => false],
                ], JSON_THROW_ON_ERROR),
            ]);

        StoreSetting::query()
            ->where('key', 'navigation.shop')
            ->firstOrFail()
            ->update([
                'value' => json_encode([
                    ['label' => 'Visible', 'latin' => 'VISIBLE', 'href' => '/shop', 'enabled' => true],
                    ['label' => 'Hidden', 'latin' => 'HIDDEN', 'href' => '/hidden', 'enabled' => false],
                ], JSON_THROW_ON_ERROR),
            ]);

        $response = $this->getJson('/api/v1/storefront/bootstrap')->assertOk();
        $announcement = $response->json('data.settings.announcement');
        $navigation = $response->json('data.settings.navigation');

        $this->assertSame(
            [['text' => 'VISIBLE', 'href' => '/shop']],
            $announcement['announcement.messages'],
        );
        $this->assertSame(
            [['label' => 'Visible', 'latin' => 'VISIBLE', 'href' => '/shop']],
            $navigation['navigation.shop'],
        );
    }

    public function test_media_paths_are_hydrated_but_http_media_is_rejected(): void
    {
        StoreSetting::query()
            ->where('key', 'home.presentation')
            ->firstOrFail()
            ->update([
                'value' => json_encode([
                    'heroImagePath' => 'storefront/hero/hero.webp',
                    'heroImageUrl' => 'http://unsafe.example/hero.jpg',
                ], JSON_THROW_ON_ERROR),
            ]);

        $response = $this->getJson('/api/v1/storefront/bootstrap')->assertOk();
        $home = $response->json('data.settings.home');
        $url = (string) $home['home.presentation']['heroImageUrl'];

        $this->assertStringContainsString('/storage/storefront/hero/hero.webp', $url);
        $this->assertStringNotContainsString('http://unsafe.example', $url);
    }

    public function test_home_products_endpoint_is_real_catalog_only_and_safe_when_catalog_is_empty(): void
    {
        $this->getJson('/api/v1/storefront/home-products')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_additive_admin_media_and_editorial_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('gallery_items', 'image_path'));
        $this->assertTrue(Schema::hasColumn('posts', 'cover_image_path'));
        $this->assertTrue(Schema::hasColumn('posts', 'meta_title'));
        $this->assertTrue(Schema::hasColumn('posts', 'meta_description'));
        $this->assertTrue(Schema::hasColumn('collections', 'cover_image_path'));
    }

    public function test_legacy_site_settings_shape_cannot_erase_new_home_extension_keys(): void
    {
        $setting = StoreSetting::query()
            ->where('key', 'home.presentation')
            ->firstOrFail();

        $setting->update([
            'value' => json_encode([
                'heroEnabled' => false,
                'heroImagePath' => 'storefront/hero/merchant.webp',
                'heroProductSlug' => 'old-product',
                'categoryOrder' => ['old'],
                'sections' => ['products'],
                'productCuration' => [
                    'mode' => 'featured',
                    'count' => 6,
                    'manualProductSlugs' => [],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $setting->update([
            'value' => json_encode([
                'heroProductSlug' => 'new-product',
                'categoryOrder' => ['new'],
                'sections' => ['ticker', 'products'],
            ], JSON_THROW_ON_ERROR),
        ]);

        $setting->refresh();
        $value = $setting->typedValue();

        $this->assertSame('new-product', $value['heroProductSlug']);
        $this->assertSame(['new'], $value['categoryOrder']);
        $this->assertFalse($value['heroEnabled']);
        $this->assertSame('storefront/hero/merchant.webp', $value['heroImagePath']);
        $this->assertSame('featured', $value['productCuration']['mode']);
        $this->assertSame(6, $value['productCuration']['count']);
    }

    public function test_legacy_file_manager_is_not_accessible_or_registered(): void
    {
        $this->assertFalse(FileManagerPage::canAccess());
        $this->assertFalse(FileManagerPage::shouldRegisterNavigation());
    }
}
