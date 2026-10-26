<?php

namespace App\Models;

use App\Enums\ExchangeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExchangeRequest extends Model
{
    protected $fillable = [
        'customer_id', 'order_id', 'order_item_id', 'source_variant_id', 'destination_variant_id',
        'destination_reservation_id', 'idempotency_key', 'request_hash', 'quantity', 'status',
        'reason', 'admin_note', 'requested_at', 'approved_at', 'completed_at', 'rejected_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $exchange): void {
            $exchange->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => ExchangeStatus::class,
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'rejected_at' => 'datetime',
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

    public function sourceVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'source_variant_id');
    }

    public function destinationVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'destination_variant_id');
    }

    public function destinationReservation(): BelongsTo
    {
        return $this->belongsTo(InventoryReservation::class, 'destination_reservation_id');
    }

    public function scopeOwnedBy(Builder $query, Customer $customer): Builder
    {
        return $query->where('customer_id', $customer->getKey());
    }
}
