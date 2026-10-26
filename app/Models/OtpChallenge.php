<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OtpChallenge extends Model
{
    protected $fillable = [
        'customer_id',
        'mobile_hash',
        'mobile_payload',
        'code_hash',
        'purpose',
        'attempts',
        'max_attempts',
        'expires_at',
        'resend_available_at',
        'consumed_at',
        'request_ip_hash',
        'user_agent_hash',
    ];

    protected $hidden = [
        'id',
        'customer_id',
        'mobile_hash',
        'mobile_payload',
        'code_hash',
        'request_ip_hash',
        'user_agent_hash',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $challenge): void {
            $challenge->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'mobile_payload' => 'encrypted',
            'expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->whereColumn('attempts', '<', 'max_attempts');
    }

    public function isUsable(): bool
    {
        return $this->consumed_at === null
            && $this->expires_at->isFuture()
            && $this->attempts < $this->max_attempts;
    }
}
