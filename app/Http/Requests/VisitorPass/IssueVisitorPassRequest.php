<?php

namespace App\Http\Requests\VisitorPass;

use Illuminate\Foundation\Http\FormRequest;

class IssueVisitorPassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'booking_id' => 'required|exists:bookings,id',
            'visitor_phone' => 'required|string|max:20',
            'visitor_name' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'booking_id.required' => 'Booking ID is required',
            'booking_id.exists' => 'Booking not found',
            'visitor_phone.required' => 'Visitor phone number is required',
            'visitor_name.required' => 'Visitor name is required',
        ];
    }
}
