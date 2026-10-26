<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PushSubscription extends Model
{
    protected $fillable = [
        'customer_id',
        'guest_token_hash',
        'marketing_enabled',
        'preferences',
        'endpoint_hash',
        'endpoint',
        'p256dh',
        'auth_token',
        'content_encoding',
        'user_agent',
        'last_seen_at',
        'revoked_at',
    ];

    protected $hidden = [
        'id',
        'customer_id',
        'guest_token_hash',
        'endpoint',
        'p256dh',
        'auth_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $subscription): void {
            $subscription->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'endpoint' => 'encrypted',
            'p256dh' => 'encrypted',
            'auth_token' => 'encrypted',
            'last_seen_at' => 'datetime',
            'marketing_enabled' => 'boolean',
            'preferences' => 'array',
            'revoked_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public static function endpointHash(string $endpoint): string
    {
        return hash('sha256', trim($endpoint));
    }
}
