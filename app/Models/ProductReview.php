<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductReview extends Model
{
    protected $fillable = [
        'customer_id',
        'order_id',
        'order_item_id',
        'product_id',
        'rating',
        'title',
        'body',
        'status',
        'moderation_note',
        'is_verified_purchase',
        'published_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $review): void {
            $review->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'is_verified_purchase' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query
            ->where('status', ReviewStatus::Approved->value)
            ->whereNotNull('published_at');
    }
}
