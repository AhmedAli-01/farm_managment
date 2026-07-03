<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'type', // 'kalagoos' or 'investor'
    ];

    /**
     * Get all the crop cycles associated with this partner.
     * Allows you to call: $partner->cropCycles
     */
    public function cropCycles(): HasMany
    {
        return $this->hasMany(CropCycle::class);
    }
}