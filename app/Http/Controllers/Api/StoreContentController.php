<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\Post;
use App\Models\StoreSetting;
use App\Support\ApiResponse;
use App\Support\Pagination;
use App\Support\PublicMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class StoreContentController extends Controller
{
    public function settings(): JsonResponse
    {
        $settings = [];
        StoreSetting::query()
            ->public()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->each(function (StoreSetting $setting) use (&$settings): void {
                Arr::set($settings, $setting->key, $setting->typedValue());
            });
        $enamadEnabled = (bool) StoreSetting::value('trust.enamad_enabled', false);
        $badgeCode = $enamadEnabled
            ? trim((string) StoreSetting::value('trust.enamad_badge_code', ''))
            : '';

        return ApiResponse::success([
            'settings' => $settings,
            'trust' => [
                'enamad' => [
                    'enabled' => $enamadEnabled && $badgeCode !== '',
                    'badgeCode' => $enamadEnabled && $badgeCode !== '' ? $badgeCode : null,
                ],
            ],
        ]);
    }

    public function page(string $slug): JsonResponse
    {
        $page = ContentPage::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return ApiResponse::success([
            'page' => [
                'id' => $page->public_id,
                'type' => $page->type,
                'slug' => $page->slug,
                'title' => $page->title,
                'excerpt' => $page->excerpt,
                'content' => $page->content,
                'seo' => [
                    'title' => $page->meta_title,
                    'description' => $page->meta_description,
                ],
                'publishedAt' => $page->published_at?->toIso8601String(),
            ],
        ]);
    }

    public function faqs(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
        ]);
        $category = trim((string) ($filters['category'] ?? ''));
        $faqs = Faq::query()
            ->active()
            ->when($category !== '', fn (Builder $query): Builder => $query->where('category', $category))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Faq $faq): array => [
                'id' => $faq->getKey(),
                'category' => $faq->category,
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])->all();

        return ApiResponse::success($faqs);
    }

    public function gallery(): JsonResponse
    {
        $items = GalleryItem::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (GalleryItem $item): array => [
                'id' => $item->getKey(),
                'title' => $item->title,
                'caption' => $item->caption,
                'imageUrl' => PublicMediaUrl::fromPathOrUrl($item->image_path)
                    ?? PublicMediaUrl::fromPathOrUrl($item->image_url),
                'linkUrl' => $item->link_url,
            ])->all();

        return ApiResponse::success($items);
    }

    public function posts(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:'.config('lbb.policies.pagination.catalog_max', 48)],
        ]);
        $category = trim((string) ($filters['category'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));
        $posts = Post::query()
            ->published()
            ->when($category !== '', fn (Builder $query): Builder => $query->where('category', $category))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            })
            ->latest('published_at')
            ->paginate((int) ($filters['perPage'] ?? config(
                'lbb.policies.pagination.catalog_default',
                12,
            )));

        return ApiResponse::success(
            $posts->getCollection()->map(fn (Post $post): array => $this->postSummary($post))->all(),
            meta: [
                'pagination' => Pagination::meta($posts),
                'filters' => [
                    'category' => $category !== '' ? $category : null,
                    'search' => $search !== '' ? $search : null,
                ],
            ],
        );
    }

    public function post(string $slug): JsonResponse
    {
        $post = Post::query()->published()->where('slug', $slug)->firstOrFail();
        Post::withoutTimestamps(
            fn (): int => Post::query()->whereKey($post->getKey())->increment('view_count'),
        );

        return ApiResponse::success([
            'post' => [
                ...$this->postSummary($post),
                'content' => $post->content,
                'viewCount' => $post->view_count + 1,
            ],
        ]);
    }

    private function postSummary(Post $post): array
    {
        return [
            'id' => $post->public_id,
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'category' => $post->category,
            'tags' => $post->tags ?? [],
            'coverUrl' => $post->cover_url,
            'author' => $post->author,
            'publishedAt' => $post->published_at?->toIso8601String(),
        ];
    }
}
