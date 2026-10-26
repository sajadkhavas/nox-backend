<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SizeGuideMeasurement extends Model
{
    protected $fillable = [
        'size_guide_id',
        'size_id',
        'measurement_definition_id',
        'value',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function sizeGuide(): BelongsTo
    {
        return $this->belongsTo(SizeGuide::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(MeasurementDefinition::class, 'measurement_definition_id');
    }
}
