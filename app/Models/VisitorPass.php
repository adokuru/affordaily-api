<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisitorPass extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'visitor_name',
        'visitor_phone',
        'visitor_id_photo_path',
        'check_in_time',
        'check_out_time',
        'is_active',
        'issued_by',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the booking that this visitor pass belongs to.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who issued this visitor pass.
     */
    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Scope to get active visitor passes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get inactive visitor passes.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}
