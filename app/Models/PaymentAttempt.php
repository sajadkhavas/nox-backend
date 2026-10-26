<?php

namespace App\Models;

use App\Enums\PaymentAttemptStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'order_id',
        'customer_id',
        'provider',
        'attempt_number',
        'idempotency_key',
        'request_hash',
        'status',
        'amount_toman',
        'amount_provider',
        'currency',
        'authority',
        'reference_id',
        'gateway_code',
        'failure_code',
        'failure_message',
        'redirect_url',
        'request_payload',
        'response_payload',
        'verification_payload',
        'expires_at',
        'verified_at',
        'failed_at',
        'cancelled_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $attempt): void {
            $attempt->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'status' => PaymentAttemptStatus::class,
            'amount_toman' => 'integer',
            'amount_provider' => 'integer',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'verification_payload' => 'array',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeOwnedBy(Builder $query, Customer $customer): Builder
    {
        return $query->where('customer_id', $customer->getKey());
    }

    public function isVerified(): bool
    {
        return $this->status === PaymentAttemptStatus::Verified;
    }
}
