<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RefundRequest extends Model
{
    protected $fillable = [
        'customer_id', 'order_id', 'return_request_id', 'payment_attempt_id', 'idempotency_key',
        'request_hash', 'status', 'amount_toman', 'currency', 'provider', 'provider_reference',
        'reason', 'failure_message', 'requested_at', 'completed_at', 'failed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $refund): void {
            $refund->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'amount_toman' => 'integer',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
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

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }
}
