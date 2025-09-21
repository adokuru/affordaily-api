<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_reference',
        'guest_id',
        'room_id',
        'guest_name',
        'guest_phone',
        'id_photo_path',
        'check_in_time',
        'check_out_time',
        'scheduled_checkout_time',
        'number_of_nights',
        'status',
        'total_amount',
        'amount_paid',
        'damage_notes',
        'key_returned',
        'auto_checkout_time',
        'auto_checkout_reason',
        'created_by',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'scheduled_checkout_time' => 'datetime',
        'auto_checkout_time' => 'datetime',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'key_returned' => 'boolean',
    ];

    /**
     * Get the guest that belongs to this booking.
     */
    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Get the room that belongs to this booking.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the user who created this booking.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the payments for this booking.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the visitor passes for this booking.
     */
    public function visitorPasses()
    {
        return $this->hasMany(VisitorPass::class);
    }

    /**
     * Get active visitor passes for this booking.
     */
    public function activeVisitorPasses()
    {
        return $this->hasMany(VisitorPass::class)->where('is_active', true);
    }

    /**
     * Calculate the remaining balance for this booking.
     */
    public function getRemainingBalanceAttribute()
    {
        return $this->total_amount - $this->amount_paid;
    }

    /**
     * Check if booking is overdue.
     */
    public function isOverdue()
    {
        return $this->status === 'active' && 
               $this->scheduled_checkout_time->isPast() && 
               $this->scheduled_checkout_time->format('H:i') === '12:00';
    }

    /**
     * Scope to get active bookings.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get pending checkout bookings.
     */
    public function scopePendingCheckout($query)
    {
        return $query->where('status', 'pending_checkout');
    }
}
