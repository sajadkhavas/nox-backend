<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Shipment extends Model
{
    protected $fillable = [
        'order_id', 'method_snapshot', 'status', 'carrier', 'tracking_reference',
        'ready_at', 'shipped_at', 'delivered_at', 'cancelled_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $shipment): void {
            $shipment->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'ready_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
