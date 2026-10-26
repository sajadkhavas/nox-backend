<?php

namespace App\Models;

use App\Enums\EvidenceState;
use App\Enums\ProductFact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductEvidence extends Model
{
    /**
     * "Evidence" is treated as an uncountable noun by Laravel's table-name
     * inflector. The apparel schema intentionally uses the explicit plural
     * table name `product_evidences`, so keep the mapping deterministic.
     */
    protected $table = 'product_evidences';

    protected $fillable = [
        'product_id',
        'fact_key',
        'state',
        'source_reference',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'state' => EvidenceState::class,
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $evidence): void {
            $evidence->public_id ??= (string) Str::ulid();
        });

        static::saving(function (self $evidence): void {
            if (! in_array((string) $evidence->fact_key, ProductFact::values(), true)) {
                throw new \DomainException('Unsupported product evidence fact.');
            }

            if ($evidence->state === EvidenceState::Verified) {
                $evidence->reviewed_at ??= now();
                $evidence->reviewed_by ??= Auth::id();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
