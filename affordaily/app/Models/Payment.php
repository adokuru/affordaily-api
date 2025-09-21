<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'payment_method',
        'amount',
        'payer_name',
        'reference',
        'is_confirmed',
        'confirmed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Get the booking that this payment belongs to.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who processed this payment.
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope to get confirmed payments.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('is_confirmed', true);
    }

    /**
     * Scope to get pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('is_confirmed', false);
    }

    /**
     * Scope to get cash payments.
     */
    public function scopeCash($query)
    {
        return $query->where('payment_method', 'cash');
    }

    /**
     * Scope to get transfer payments.
     */
    public function scopeTransfer($query)
    {
        return $query->where('payment_method', 'transfer');
    }
}
