<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'title',
        'recipient_name',
        'mobile',
        'province',
        'city',
        'address_line',
        'postal_code',
        'is_default',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $address): void {
            $address->public_id ??= (string) Str::ulid();
        });

        static::saved(function (self $address): void {
            if (! $address->is_default) {
                return;
            }

            static::query()
                ->where('customer_id', $address->customer_id)
                ->where('id', '!=', $address->getKey())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        });
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeOwnedBy(Builder $query, Customer $customer): Builder
    {
        return $query->where('customer_id', $customer->getKey());
    }
}
