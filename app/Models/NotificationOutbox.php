<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NotificationOutbox extends Model
{
    protected $table = 'notification_outbox';

    protected $fillable = [
        'customer_id',
        'order_id',
        'channel',
        'destination',
        'template_key',
        'payload',
        'status',
        'provider',
        'provider_message_id',
        'attempts',
        'last_error',
        'available_at',
        'sent_at',
        'failed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $notification): void {
            $notification->public_id ??= (string) Str::ulid();
            $notification->available_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'destination' => 'encrypted',
            'payload' => 'array',
            'status' => NotificationStatus::class,
            'attempts' => 'integer',
            'available_at' => 'datetime',
            'sent_at' => 'datetime',
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

    public function scopeReady(Builder $query): Builder
    {
        return $query
            ->where('status', NotificationStatus::Pending->value)
            ->where(function (Builder $query): void {
                $query->whereNull('available_at')->orWhere('available_at', '<=', now());
            });
    }
}
