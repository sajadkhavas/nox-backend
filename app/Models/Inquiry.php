<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Inquiry extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'full_name',
        'mobile',
        'email',
        'subject',
        'message',
        'metadata',
        'status',
        'ip_hash',
        'user_agent_hash',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $inquiry): void {
            $inquiry->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'type' => InquiryType::class,
            'status' => InquiryStatus::class,
            'metadata' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
