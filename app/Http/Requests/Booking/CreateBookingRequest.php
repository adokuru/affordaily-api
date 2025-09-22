<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
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
            'guest_name' => 'required|string|max:255',
            'guest_phone' => 'required|string|max:20',
            'id_photo_path' => 'nullable|string',
            'number_of_nights' => 'required|integer|min:1|max:30',
            'preferred_bed_type' => 'nullable|in:A,B',
            'payment_method' => 'required|in:cash,transfer',
            'payer_name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
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
            'guest_name.required' => 'Guest name is required',
            'guest_phone.required' => 'Guest phone number is required',
            'number_of_nights.required' => 'Number of nights is required',
            'number_of_nights.min' => 'Number of nights must be at least 1',
            'number_of_nights.max' => 'Number of nights cannot exceed 30',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Payment method must be either cash or transfer',
            'payer_name.required' => 'Payer name is required',
        ];
    }
}
