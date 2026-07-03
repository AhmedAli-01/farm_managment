<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Field extends Model
{
    // Allows these columns to be populated during forms/creation
    protected $fillable = [
        'name',
        'total_area',
        'latitude',
        'longitude',
    ];

    /**
     * Get all of the plots inside this field.
     * This allows you to call: $field->plots
     */
    public function plots(): HasMany
    {
        return $this->hasMany(Plot::class);
    }
}