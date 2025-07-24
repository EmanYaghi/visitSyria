<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReserveRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            'trip_id' => 'required|exists:trips,id',
            'number_of_tickets' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
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
