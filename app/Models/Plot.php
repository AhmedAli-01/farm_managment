<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plot extends Model
{
    protected $fillable = [
        'field_id',
        'name',
        'size',
        'status',
    ];

    /**
     * Get the field that owns this plot.
     * This allows you to call: $plot->field->name
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * Get all of the crop cycles recorded for this plot.
     * This will be used later when connecting crops to plots.
     */
    public function cropCycles(): HasMany
    {
        return $this->hasMany(CropCycle::class);
    }
}