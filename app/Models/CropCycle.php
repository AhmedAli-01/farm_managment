<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CropCycle extends Model
{
    protected $fillable = [
        'plot_id',
        'partner_id',
        'crop_name',
        'operation_type', // 'direct', 'kalagoos', 'managed'
        'planting_date',
        'weather_at_planting',
        'owner_profit_share',
        'status', // 'growing', 'harvested', 'failed'
    ];

    /**
     * Get the plot where this crop cycle is taking place.
     */
    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }

    /**
     * Get the partner tied to this crop cycle (if any).
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get all agronomic, treatment, and financial logs for this cycle.
     * Allows you to call: $cropCycle->logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(CropLog::class);
    }
}