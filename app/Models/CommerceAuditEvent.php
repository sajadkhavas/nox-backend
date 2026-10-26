<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CommerceAuditEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'event_key', 'subject_type', 'subject_public_id', 'actor_type', 'actor_id',
        'request_id', 'metadata', 'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            $event->public_id ??= (string) Str::ulid();
            $event->created_at ??= now();
        });
    }

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
