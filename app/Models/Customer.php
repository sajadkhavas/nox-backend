<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'mobile',
        'full_name',
        'email',
        'mobile_verified_at',
        'last_login_at',
        'is_active',
        'marketing_consent',
    ];

    protected $hidden = [
        'id',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $customer): void {
            $customer->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'mobile_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'marketing_consent' => 'boolean',
        ];
    }

    public function otpChallenges(): HasMany
    {
        return $this->hasMany(OtpChallenge::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('placed_at');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->latest('is_default')->latest('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest('id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class)->latest('id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(NotificationOutbox::class)->latest('id');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class)->latest('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }
}
