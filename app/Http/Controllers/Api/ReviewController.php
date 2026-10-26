<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitReviewRequest;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductReview;
use App\Support\ApiResponse;
use App\Support\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:'.config('lbb.policies.pagination.account_max', 30)],
        ]);
        $product = Product::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $reviews = ProductReview::query()
            ->approved()
            ->where('product_id', $product->getKey())
            ->with('customer:id,full_name')
            ->latest('published_at')
            ->paginate((int) ($filters['perPage'] ?? config(
                'lbb.policies.pagination.account_default',
                10,
            )));
        $summary = ProductReview::query()
            ->approved()
            ->where('product_id', $product->getKey())
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating')
            ->first();

        return ApiResponse::success(
            $reviews->getCollection()->map(fn (ProductReview $review): array => [
                'id' => $review->public_id,
                'rating' => $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'verifiedPurchase' => $review->is_verified_purchase,
                'customerName' => $review->customer?->full_name
                    ? Str::before($review->customer->full_name, ' ')
                    : 'مشتری LBB',
                'publishedAt' => $review->published_at?->toIso8601String(),
            ])->all(),
            meta: [
                'summary' => [
                    'count' => (int) ($summary?->review_count ?? 0),
                    'averageRating' => round((float) ($summary?->average_rating ?? 0), 2),
                ],
                'pagination' => Pagination::meta($reviews),
            ],
        );
    }

    public function store(
        SubmitReviewRequest $request,
        string $orderId,
    ): JsonResponse {
        $review = DB::transaction(function () use ($request, $orderId): ProductReview {
            $order = Order::query()
                ->ownedBy($request->user('customer'))
                ->where('public_id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== OrderStatus::Delivered) {
                throw ValidationException::withMessages([
                    'order' => ['ثبت نظر فقط پس از تحویل سفارش امکان‌پذیر است.'],
                ]);
            }

            $item = OrderItem::query()
                ->where('order_id', $order->getKey())
                ->where('public_id', $request->validated('orderItemId'))
                ->lockForUpdate()
                ->firstOrFail();

            if (! $item->product_id) {
                throw ValidationException::withMessages([
                    'orderItemId' => ['محصول این قلم دیگر در کاتالوگ موجود نیست.'],
                ]);
            }

            if (ProductReview::query()
                ->where('customer_id', $request->user('customer')->getKey())
                ->where('order_item_id', $item->getKey())
                ->exists()) {
                throw ValidationException::withMessages([
                    'orderItemId' => ['برای این قلم قبلاً نظر ثبت شده است.'],
                ]);
            }

            return ProductReview::query()->create([
                'customer_id' => $request->user('customer')->getKey(),
                'order_id' => $order->getKey(),
                'order_item_id' => $item->getKey(),
                'product_id' => $item->product_id,
                'rating' => (int) $request->validated('rating'),
                'title' => $this->nullableTrim($request->validated('title')),
                'body' => $this->nullableTrim($request->validated('body')),
                'status' => ReviewStatus::Pending,
                'is_verified_purchase' => true,
            ]);
        }, 3);

        return ApiResponse::success([
            'review' => [
                'id' => $review->public_id,
                'status' => $review->status->value,
                'statusLabel' => $review->status->label(),
            ],
        ], 'نظر شما ثبت شد و پس از بررسی نمایش داده می‌شود.', 201);
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
