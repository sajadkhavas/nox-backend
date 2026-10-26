<?php

namespace App\Models;

use App\Enums\InventoryLedgerEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InventoryLedgerEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'variant_id', 'order_id', 'reservation_id', 'event_type', 'on_hand_delta',
        'reserved_delta', 'on_hand_after', 'reserved_after', 'available_after',
        'correlation_type', 'correlation_public_id', 'reason', 'actor_type', 'actor_id',
        'idempotency_key', 'metadata', 'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $entry): void {
            $entry->public_id ??= (string) Str::ulid();
            $entry->created_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'event_type' => InventoryLedgerEventType::class,
            'on_hand_delta' => 'integer',
            'reserved_delta' => 'integer',
            'on_hand_after' => 'integer',
            'reserved_after' => 'integer',
            'available_after' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(InventoryReservation::class, 'reservation_id');
    }
}
