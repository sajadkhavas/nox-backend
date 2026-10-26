<?php

namespace App\Models;

use App\Enums\CheckoutQuoteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CheckoutQuote extends Model
{
    protected $fillable = [
        'customer_id', 'request_hash', 'status', 'delivery_method', 'delivery_zone_id',
        'items_snapshot', 'recipient_snapshot', 'subtotal_toman', 'delivery_fee_toman',
        'packaging_fee_toman', 'discount_total_toman', 'grand_total_toman', 'currency',
        'expires_at', 'consumed_at', 'consumed_order_public_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $quote): void {
            $quote->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => CheckoutQuoteStatus::class,
            'items_snapshot' => 'array',
            'recipient_snapshot' => 'array',
            'subtotal_toman' => 'integer',
            'delivery_fee_toman' => 'integer',
            'packaging_fee_toman' => 'integer',
            'discount_total_toman' => 'integer',
            'grand_total_toman' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function isUsable(): bool
    {
        return $this->status === CheckoutQuoteStatus::Active && $this->expires_at->isFuture();
    }
}
