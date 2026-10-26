<?php

namespace App\Models;

use App\Enums\InventoryReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InventoryReservation extends Model
{
    protected $fillable = [
        'order_id', 'variant_id', 'purpose', 'correlation_key', 'quantity', 'status', 'expires_at',
        'released_at', 'consumed_at', 'restocked_at', 'release_reason',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $reservation): void {
            $reservation->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => InventoryReservationStatus::class,
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'consumed_at' => 'datetime',
            'restocked_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(InventoryLedgerEntry::class, 'reservation_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', InventoryReservationStatus::Active->value)->where('expires_at', '>', now());
    }

    public function isActive(): bool
    {
        return $this->status === InventoryReservationStatus::Active && $this->expires_at->isFuture();
    }
}
