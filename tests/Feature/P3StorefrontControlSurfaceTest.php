<?php

namespace Tests\Feature;

use App\Models\ContentPage;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\Post;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class P3StorefrontControlSurfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_exposes_only_public_typed_storefront_settings(): void
    {
        StoreSetting::query()->create([
            'group' => 'trust',
            'key' => 'trust.secret_test',
            'type' => 'string',
            'value' => 'must-not-leak',
            'label' => 'Private test value',
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/v1/storefront/bootstrap')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.contractVersion', '2026-09-06-p3-storefront-v1');
        $payload = $response->json('data');

        $this->assertSame('2026-09-06-p3-storefront-v1', $payload['contractVersion']);
        $this->assertSame('LBB', $payload['settings']['brand']['brand.identity']['name']);
        $this->assertSame('از پینترست تا رگال LBB', $payload['settings']['brand']['brand.copy']['heroTitle']);
        $this->assertSame('026-3256-0477', $payload['settings']['contact']['contact.public']['phone']);
        $this->assertSame('lbb-signature-tee', $payload['settings']['home']['home.presentation']['heroProductSlug']);
        $this->assertIsArray($payload['settings']['navigation']['navigation.shop']);
        $this->assertStringNotContainsString('must-not-leak', $response->getContent());
        $this->assertStringNotContainsString('trust.secret_test', $response->getContent());
    }

    public function test_p3_migration_registers_only_verified_public_pages(): void
    {
        $this->getJson('/api/v1/storefront/pages/about')
            ->assertOk()
            ->assertJsonPath('meta.contractVersion', '2026-09-06-p3-storefront-v1')
            ->assertJsonPath('data.title', 'درباره LBB');

        $this->getJson('/api/v1/storefront/pages/contact')
            ->assertOk()
            ->assertJsonPath('data.title', 'تماس با LBB')
            ->assertJsonFragment(['content' => '<p>فروشگاه حضوری LBB: کرج، پاساژ مهستان.</p><p>تلفن: 026-3256-0477</p><p>واتساپ: 0902-858-4879</p><p>اینستاگرام: @lbbclo</p>']);

        $this->assertDatabaseMissing('content_pages', ['slug' => 'terms']);
        $this->assertDatabaseMissing('content_pages', ['slug' => 'privacy']);
        $this->assertDatabaseMissing('content_pages', ['slug' => 'shipping-returns']);
    }

    public function test_versioned_content_routes_publish_only_public_content(): void
    {
        ContentPage::query()->create([
            'type' => 'page',
            'slug' => 'about-live',
            'title' => 'درباره LBB',
            'content' => '<p>Live content</p>',
            'meta_title' => 'About live',
            'meta_description' => 'Published page',
            'status' => 'published',
            'published_at' => now(),
        ]);
        ContentPage::query()->create([
            'type' => 'page',
            'slug' => 'private-draft',
            'title' => 'Draft',
            'content' => 'Draft content',
            'status' => 'draft',
        ]);

        Faq::query()->create([
            'category' => 'shipping',
            'question' => 'ارسال؟',
            'answer' => 'پاسخ',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        Faq::query()->create([
            'category' => 'shipping',
            'question' => 'Hidden FAQ',
            'answer' => 'Hidden',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        GalleryItem::query()->create([
            'title' => 'Look 1',
            'caption' => 'Street look',
            'image_url' => '/look-1.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        GalleryItem::query()->create([
            'title' => 'Hidden look',
            'image_url' => '/hidden.jpg',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        Post::query()->create([
            'slug' => 'live-post',
            'title' => 'Live Post',
            'excerpt' => 'Excerpt',
            'content' => '<p>Article</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);
        Post::query()->create([
            'slug' => 'draft-post',
            'title' => 'Draft Post',
            'content' => 'Hidden',
            'status' => 'draft',
        ]);

        $this->getJson('/api/v1/storefront/pages/about-live')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.contractVersion', '2026-09-06-p3-storefront-v1')
            ->assertJsonPath('data.title', 'درباره LBB');
        $this->getJson('/api/v1/storefront/pages/private-draft')->assertNotFound();

        $faq = $this->getJson('/api/v1/storefront/faqs')
            ->assertOk()
            ->assertJsonPath('meta.contractVersion', '2026-09-06-p3-storefront-v1');
        $this->assertCount(1, $faq->json('data'));
        $this->assertSame('ارسال؟', $faq->json('data.0.question'));

        $lookbook = $this->getJson('/api/v1/storefront/lookbook')->assertOk();
        $this->assertCount(1, $lookbook->json('data'));
        $this->assertSame('Look 1', $lookbook->json('data.0.title'));

        $journal = $this->getJson('/api/v1/storefront/journal')->assertOk();
        $this->assertCount(1, $journal->json('data'));
        $this->assertSame('live-post', $journal->json('data.0.slug'));
        $this->getJson('/api/v1/storefront/journal/live-post')
            ->assertOk()
            ->assertJsonPath('meta.contractVersion', '2026-09-06-p3-storefront-v1')
            ->assertJsonPath('data.content', '<p>Article</p>');
        $this->getJson('/api/v1/storefront/journal/draft-post')->assertNotFound();
    }
}
