<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'id_photo_path',
        'notes',
        'total_stays',
        'total_spent',
        'last_stay',
        'is_blacklisted',
        'blacklist_reason',
    ];

    protected $casts = [
        'total_spent' => 'decimal:2',
        'last_stay' => 'datetime',
        'is_blacklisted' => 'boolean',
    ];

    /**
     * Get the bookings for this guest.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'guest_id');
    }

    /**
     * Get the payments for this guest.
     */
    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Booking::class, 'guest_id');
    }

    /**
     * Get the visitor passes for this guest.
     */
    public function visitorPasses()
    {
        return $this->hasMany(VisitorPass::class);
    }

    /**
     * Scope to get non-blacklisted guests.
     */
    public function scopeNotBlacklisted($query)
    {
        return $query->where('is_blacklisted', false);
    }

    /**
     * Scope to search guests by phone.
     */
    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', $phone);
    }

    /**
     * Update guest statistics after a booking.
     */
    public function updateStats($amount)
    {
        $this->increment('total_stays');
        $this->increment('total_spent', $amount);
        $this->update(['last_stay' => now()]);
    }
}
