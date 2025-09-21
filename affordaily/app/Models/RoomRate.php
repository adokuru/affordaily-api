<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'bed_type',
        'rate_per_night',
        'is_active',
    ];

    protected $casts = [
        'rate_per_night' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get active rates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get rates by bed type.
     */
    public function scopeByBedType($query, $bedType)
    {
        return $query->where('bed_type', $bedType);
    }
}
