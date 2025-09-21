<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'bed_type',
        'is_available',
        'description',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    /**
     * Get the bookings for this room.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the active booking for this room.
     */
    public function activeBooking()
    {
        return $this->hasOne(Booking::class)->where('status', 'active');
    }

    /**
     * Get the current rate for this room type.
     */
    public function currentRate()
    {
        return $this->hasOne(RoomRate::class, 'bed_type', 'bed_type')->where('is_active', true);
    }

    /**
     * Scope to get available rooms.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to get rooms by bed type.
     */
    public function scopeByBedType($query, $bedType)
    {
        return $query->where('bed_type', $bedType);
    }
}
