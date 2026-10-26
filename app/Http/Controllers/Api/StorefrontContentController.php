<?php

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\PublicCatalogQuery;
use App\Domain\Catalog\PublicCatalogTransformer;
use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\Post;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\ApiResponse;
use App\Support\PublicMediaUrl;
use App\Support\StorefrontPresentationDefaults;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class StorefrontContentController extends Controller
{
    public const CONTRACT_VERSION = '2026-09-06-p3-storefront-v1';

    public function bootstrap(): JsonResponse
    {
        $settings = StorefrontPresentationDefaults::merge($this->publicSettings());
        $settings = $this->hydratePresentationMedia($settings);
        $settings = $this->filterDisabledPublicRows($settings);

        $paymentProvider = trim((string) config('lbb.payment.provider', 'disabled'));
        $paymentEnabled = (bool) config('lbb.payment.enabled', false)
            && $paymentProvider !== ''
            && $paymentProvider !== 'disabled';

        return ApiResponse::success([
            'contractVersion' => self::CONTRACT_VERSION,
            'settings' => $settings,
            'runtime' => [
                'checkoutEnabled' => (bool) config('lbb.checkout.enabled', false),
                'payment' => [
                    'enabled' => $paymentEnabled,
                    'provider' => $paymentEnabled ? $paymentProvider : 'disabled',
                ],
            ],
        ]);
    }

    public function homeProducts(
        PublicCatalogQuery $catalog,
        PublicCatalogTransformer $transformer,
    ): JsonResponse {
        $settings = StorefrontPresentationDefaults::merge($this->publicSettings());
        $presentation = is_array($settings['home']['home.presentation'] ?? null)
            ? $settings['home']['home.presentation']
            : [];
        $curation = is_array($presentation['productCuration'] ?? null)
            ? $presentation['productCuration']
            : [];
        $mode = in_array(($curation['mode'] ?? 'newest'), ['newest', 'featured', 'manual'], true)
            ? (string) $curation['mode']
            : 'newest';
        $count = max(1, min(12, (int) ($curation['count'] ?? 4)));

        $query = $catalog->publishedProducts();
        $products = match ($mode) {
            'featured' => $query
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->orderByDesc('published_at')
                ->orderByDesc('products.id')
                ->limit($count)
                ->get(),
            'manual' => $this->manualHomeProducts(
                $query,
                $this->stringList($curation['manualProductSlugs'] ?? []),
                $count,
            ),
            default => $query
                ->orderByDesc('published_at')
                ->orderByDesc('products.id')
                ->limit($count)
                ->get(),
        };

        return ApiResponse::success(
            $products
                ->map(fn ($product): array => $transformer->productSummary($product))
                ->values()
                ->all(),
        );
    }

    public function page(string $slug): JsonResponse
    {
        $page = ContentPage::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return ApiResponse::success([
            'slug' => $page->slug,
            'type' => $page->type,
            'title' => $page->title,
            'excerpt' => $page->excerpt,
            'content' => $page->content,
            'metaTitle' => $page->meta_title,
            'metaDescription' => $page->meta_description,
            'publishedAt' => $page->published_at?->toIso8601String(),
        ]);
    }

    public function faqs(): JsonResponse
    {
        return ApiResponse::success(
            Faq::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['category', 'question', 'answer', 'sort_order'])
                ->map(fn (Faq $faq): array => [
                    'category' => $faq->category,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'sortOrder' => $faq->sort_order,
                ])
                ->values(),
        );
    }

    public function lookbook(): JsonResponse
    {
        return ApiResponse::success(
            GalleryItem::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['title', 'caption', 'image_path', 'image_url', 'link_url', 'sort_order'])
                ->map(fn (GalleryItem $item): array => [
                    'title' => $item->title,
                    'caption' => $item->caption,
                    'imageUrl' => PublicMediaUrl::fromPathOrUrl($item->image_path)
                        ?? PublicMediaUrl::fromPathOrUrl($item->image_url),
                    'linkUrl' => $this->safePublicHref($item->link_url),
                    'sortOrder' => $item->sort_order,
                ])
                ->filter(fn (array $item): bool => is_string($item['imageUrl']) && $item['imageUrl'] !== '')
                ->values(),
        );
    }

    public function journal(): JsonResponse
    {
        return ApiResponse::success(
            Post::query()
                ->published()
                ->latest('published_at')
                ->get([
                    'public_id',
                    'slug',
                    'title',
                    'excerpt',
                    'category',
                    'tags',
                    'cover_image_path',
                    'cover_url',
                    'author',
                    'published_at',
                    'meta_title',
                    'meta_description',
                ])
                ->map(fn (Post $post): array => $this->postSummary($post))
                ->values(),
        );
    }

    public function journalPost(string $slug): JsonResponse
    {
        $post = Post::query()->published()->where('slug', $slug)->firstOrFail();

        return ApiResponse::success([
            ...$this->postSummary($post),
            'content' => $post->content,
        ]);
    }

    /** @return array<string, mixed> */
    private function postSummary(Post $post): array
    {
        return [
            'publicId' => $post->public_id,
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'category' => $post->category,
            'tags' => $post->tags ?? [],
            'coverUrl' => PublicMediaUrl::fromPathOrUrl($post->cover_image_path)
                ?? PublicMediaUrl::fromPathOrUrl($post->cover_url),
            'author' => $post->author,
            'publishedAt' => $post->published_at?->toIso8601String(),
            'metaTitle' => $post->meta_title,
            'metaDescription' => $post->meta_description,
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function publicSettings(): array
    {
        $settings = [];

        StoreSetting::query()
            ->public()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->each(function (StoreSetting $setting) use (&$settings): void {
                $settings[$setting->group][$setting->key] = $setting->typedValue();
            });

        return $settings;
    }

    /**
     * @param  array<string, array<string, mixed>>  $settings
     * @return array<string, array<string, mixed>>
     */
    private function hydratePresentationMedia(array $settings): array
    {
        $home = $settings['home']['home.presentation'] ?? null;
        if (is_array($home)) {
            $home['heroImageUrl'] = PublicMediaUrl::fromPathOrUrl($home['heroImagePath'] ?? null)
                ?? PublicMediaUrl::fromPathOrUrl($home['heroImageUrl'] ?? null);
            $settings['home']['home.presentation'] = $home;
        }

        $localStore = $settings['home']['home.local_store'] ?? null;
        if (is_array($localStore)) {
            $localStore['imageUrl'] = PublicMediaUrl::fromPathOrUrl($localStore['imagePath'] ?? null)
                ?? PublicMediaUrl::fromPathOrUrl($localStore['imageUrl'] ?? null);
            $settings['home']['home.local_store'] = $localStore;
        }

        $pages = $settings['page']['page.presentation'] ?? null;
        if (is_array($pages)) {
            foreach ($pages as $key => $page) {
                if (! is_array($page)) {
                    continue;
                }
                $page['socialImageUrl'] = PublicMediaUrl::fromPathOrUrl($page['socialImagePath'] ?? null)
                    ?? PublicMediaUrl::fromPathOrUrl($page['socialImageUrl'] ?? null);
                $pages[$key] = $page;
            }
            $settings['page']['page.presentation'] = $pages;
        }

        return $settings;
    }

    /**
     * Store disabled rows for Admin recoverability, but never expose them as
     * active navigation/announcement items to the existing public consumer.
     *
     * @param  array<string, array<string, mixed>>  $settings
     * @return array<string, array<string, mixed>>
     */
    private function filterDisabledPublicRows(array $settings): array
    {
        foreach (['shop', 'editorial', 'service', 'brand'] as $key) {
            $path = 'navigation.'.$key;
            $rows = $settings['navigation'][$path] ?? null;
            if (is_array($rows)) {
                $settings['navigation'][$path] = $this->enabledRows($rows);
            }
        }

        $announcements = $settings['announcement']['announcement.messages'] ?? null;
        if (is_array($announcements)) {
            $settings['announcement']['announcement.messages'] = $this->enabledRows($announcements);
        }

        return $settings;
    }

    /** @param array<mixed> $rows @return list<array<string, mixed>> */
    private function enabledRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->filter(fn (array $row): bool => ($row['enabled'] ?? true) !== false)
            ->map(function (array $row): array {
                unset($row['enabled']);

                return $row;
            })
            ->values()
            ->all();
    }

    /** @param mixed $value @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn (mixed $item): bool => is_string($item))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Product>  $query
     * @return Collection<int, Product>
     */
    private function manualHomeProducts($query, array $slugs, int $count): Collection
    {
        if ($slugs === []) {
            return collect();
        }

        $bySlug = $query->whereIn('slug', $slugs)->get()->keyBy('slug');

        return collect($slugs)
            ->map(fn (string $slug) => $bySlug->get($slug))
            ->filter()
            ->take($count)
            ->values();
    }

    private function safePublicHref(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $href = trim($value);
        if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
            return $href;
        }

        return filter_var($href, FILTER_VALIDATE_URL) !== false
            && parse_url($href, PHP_URL_SCHEME) === 'https'
                ? $href
                : null;
    }
}
