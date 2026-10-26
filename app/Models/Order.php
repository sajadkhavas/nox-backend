<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'customer_id', 'checkout_quote_id', 'order_number', 'idempotency_key', 'request_hash', 'status', 'payment_status',
        'delivery_method', 'delivery_zone_id', 'subtotal_toman', 'delivery_fee_toman', 'packaging_fee_toman',
        'discount_total_toman', 'grand_total_toman', 'currency', 'item_count', 'preparation_time_days', 'preparation_max_days',
        'customer_name', 'customer_mobile', 'province', 'city', 'address', 'postal_code', 'notes', 'tracking_code',
        'reservation_expires_at', 'placed_at', 'paid_at', 'confirmed_at', 'preparing_at', 'ready_at', 'dispatched_at',
        'delivered_at', 'cancelled_at', 'admin_cancelled_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            $order->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class, 'payment_status' => PaymentStatus::class, 'delivery_method' => DeliveryMethod::class,
            'subtotal_toman' => 'integer', 'delivery_fee_toman' => 'integer', 'packaging_fee_toman' => 'integer',
            'discount_total_toman' => 'integer', 'grand_total_toman' => 'integer', 'item_count' => 'integer',
            'preparation_time_days' => 'integer', 'preparation_max_days' => 'integer', 'reservation_expires_at' => 'datetime',
            'placed_at' => 'datetime', 'paid_at' => 'datetime', 'confirmed_at' => 'datetime', 'preparing_at' => 'datetime',
            'ready_at' => 'datetime', 'dispatched_at' => 'datetime', 'delivered_at' => 'datetime', 'cancelled_at' => 'datetime',
            'admin_cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function checkoutQuote(): BelongsTo
    {
        return $this->belongsTo(CheckoutQuote::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class)->latest('id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(OrderInternalNote::class)->latest('created_at');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(NotificationOutbox::class)->latest('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest('id');
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRequest::class)->latest('id');
    }

    public function exchanges(): HasMany
    {
        return $this->hasMany(ExchangeRequest::class)->latest('id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(RefundRequest::class)->latest('id');
    }

    public function inventoryLedgerEntries(): HasMany
    {
        return $this->hasMany(InventoryLedgerEntry::class)->orderBy('id');
    }

    public function scopeOwnedBy(Builder $query, Customer $customer): Builder
    {
        return $query->where('customer_id', $customer->getKey());
    }

    public function canBeCancelledByCustomer(): bool
    {
        return $this->status === OrderStatus::AwaitingPayment
            && in_array($this->payment_status, [PaymentStatus::Unpaid, PaymentStatus::Failed], true);
    }
}
