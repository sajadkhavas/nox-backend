<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeliveryZone extends Model
{
    protected $fillable = [
        'name',
        'province',
        'city',
        'standard_enabled',
        'pickup_enabled',
        'standard_fee_toman',
        'pickup_fee_toman',
        'immediate_courier_enabled',
        'tipax_enabled',
        'decapost_enabled',
        'express_post_enabled',
        'immediate_courier_fee_toman',
        'tipax_fee_toman',
        'decapost_fee_toman',
        'express_post_fee_toman',
        'packaging_fee_toman',
        'minimum_order_toman',
        'free_delivery_threshold_toman',
        'preparation_min_days',
        'preparation_max_days',
        'daily_order_limit',
        'priority',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $zone): void {
            $zone->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'standard_enabled' => 'boolean',
            'pickup_enabled' => 'boolean',
            'standard_fee_toman' => 'integer',
            'pickup_fee_toman' => 'integer',
            'immediate_courier_enabled' => 'boolean',
            'tipax_enabled' => 'boolean',
            'decapost_enabled' => 'boolean',
            'express_post_enabled' => 'boolean',
            'immediate_courier_fee_toman' => 'integer',
            'tipax_fee_toman' => 'integer',
            'decapost_fee_toman' => 'integer',
            'express_post_fee_toman' => 'integer',
            'packaging_fee_toman' => 'integer',
            'minimum_order_toman' => 'integer',
            'free_delivery_threshold_toman' => 'integer',
            'preparation_min_days' => 'integer',
            'preparation_max_days' => 'integer',
            'daily_order_limit' => 'integer',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function methodEnabled(DeliveryMethod $method): bool
    {
        return (bool) $this->getAttribute("{$method->value}_enabled");
    }

    public function feeFor(DeliveryMethod $method, int $subtotalToman): int
    {
        $threshold = $this->free_delivery_threshold_toman;
        if ($threshold !== null && $subtotalToman >= $threshold && $method !== DeliveryMethod::Pickup) {
            return 0;
        }

        return (int) $this->getAttribute("{$method->value}_fee_toman");
    }
}
