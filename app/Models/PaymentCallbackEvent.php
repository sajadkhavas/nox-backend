<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentCallbackEvent extends Model
{
    protected $fillable = [
        'payment_attempt_id', 'order_id', 'provider', 'authority', 'request_hash', 'status',
        'provider_reference', 'received_at', 'processed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            $event->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'processed_at' => 'datetime'];
    }

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
