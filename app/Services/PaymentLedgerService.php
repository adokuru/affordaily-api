<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentLedgerService
{
    public function validatedFilters(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'sometimes|integer|exists:bookings,id',
            'payment_method' => 'sometimes|in:cash,transfer',
            'is_confirmed' => 'sometimes|boolean',
            'confirmed' => 'sometimes|boolean',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function query(array $filters = []): Builder
    {
        $query = Payment::with(['booking.room', 'processedBy']);

        if (isset($filters['booking_id'])) {
            $query->where('booking_id', $filters['booking_id']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (array_key_exists('is_confirmed', $filters)) {
            $query->where('is_confirmed', $this->booleanValue($filters['is_confirmed']));
        }

        if (array_key_exists('confirmed', $filters)) {
            $query->where('is_confirmed', $this->booleanValue($filters['confirmed']));
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    public function summary(Builder $query): array
    {
        return [
            'total_amount' => (float) (clone $query)->sum('amount'),
            'cash_total' => (float) (clone $query)->where('payment_method', 'cash')->sum('amount'),
            'transfer_total' => (float) (clone $query)->where('payment_method', 'transfer')->sum('amount'),
            'confirmed_total' => (float) (clone $query)->where('is_confirmed', true)->sum('amount'),
            'pending_total' => (float) (clone $query)->where('is_confirmed', false)->sum('amount'),
        ];
    }

    private function booleanValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
