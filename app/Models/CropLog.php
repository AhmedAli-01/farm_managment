<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CropLog extends Model
{
    protected $fillable = [
        'crop_cycle_id',
        'log_type', // 'progress', 'fertilizer', 'infection', 'treatment', 'harvest'
        'title',
        'notes',
        'photo_path',
        'amount',
        'currency', // 'USD' or 'SOS'
        'amount_in_usd',
    ];

    /**
     * Get the crop cycle that this log belongs to.
     */
    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }
}