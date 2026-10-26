<?php

namespace App\Models;

use App\Enums\ReturnResolution;
use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReturnRequest extends Model
{
    protected $fillable = [
        'customer_id', 'order_id', 'idempotency_key', 'request_hash', 'status', 'resolution',
        'reason', 'admin_note', 'requested_at', 'approved_at', 'rejected_at', 'received_at', 'resolved_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $return): void {
            $return->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ReturnStatus::class,
            'resolution' => ReturnResolution::class,
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'received_at' => 'datetime',
            'resolved_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function scopeOwnedBy(Builder $query, Customer $customer): Builder
    {
        return $query->where('customer_id', $customer->getKey());
    }
}
