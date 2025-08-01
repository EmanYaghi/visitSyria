<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class ReserveTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'trip_id' => 'required|exists:trips,id',
            'number_of_tickets' => 'required|integer|min:1',
            'passengers' => 'required|array|min:1',
            'passengers.*.first_name' => 'required|string|max:255',
            'passengers.*.last_name' => 'required|string|max:255',
            'passengers.*.gender' => 'nullable|in:male,female,other',
            'passengers.*.birth_date' => 'nullable|date',
            'passengers.*.nationality' => 'nullable|string|max:255',
            'passengers.0.email' => 'required|email',
            'passengers.0.phone' => 'required|string|max:20',
            'passengers.0.country_code' => 'required|string|max:10',
        ];
    }
}
