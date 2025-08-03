<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReserveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        if('type'=='trip')
            return [
                'id' => 'required|exists:trips,id',
                'number_of_tickets' => 'required|integer|min:1',
                'passengers' => 'required|array|min:1',
                'passengers.*.first_name' => 'required|string|max:255',
                'passengers.*.last_name' => 'required|string|max:255',
                'passengers.*.gender' => 'required|in:male,female,other',
                'passengers.*.birth_date' => 'required|date',
                'passengers.*.nationality' => 'required|string|max:255',
                'passengers.0.email' => 'required|email',
                'passengers.0.phone' => 'required|string|max:20',
                'passengers.0.country_code' => 'required|string|max:10',
            ];
        else if('type'=='event')
            return [
                'id' => 'required|exists:events,id',
                'number_of_tickets' => 'required|integer|min:1',
                'passengers' => 'required|array|min:1',
                'passengers.*.first_name' => 'required|string|max:255',
                'passengers.*.last_name' => 'required|string|max:255',
                'passengers.*.gender' => 'required|in:male,female,other',
                'passengers.*.birth_date' => 'required|date',
                'passengers.*.nationality' => 'required|string|max:255',
                'passengers.0.email' => 'required|email',
                'passengers.0.phone' => 'required|string|max:20',
                'passengers.0.country_code' => 'required|string|max:10',
            ];
        else if('type'=='flight')
            return [
                'id' => 'required|exists:flights,id',
                'number_of_adults' => 'required|integer|min:1',
                'number_of_children' => 'nullable|integer',
                'number_of_infants' => 'nullable|integer',
                'passengers' => 'required|array|min:1',
                'passengers.*.first_name' => 'required|string|max:255',
                'passengers.*.last_name' => 'required|string|max:255',
                'passengers.*.gender' => 'required|in:male,female,other',
                'passengers.*.birth_date' => 'required|date',
                'passengers.*.nationality' => 'required|string|max:255',
                'passenger.*.passport_number'=>'required|string',
                'passenger.*.passport_expiry_date'=>'required|date',
                'passengers.0.email' => 'required|email',
                'passengers.0.phone' => 'required|string|max:20',
                'passengers.0.country_code' => 'required|string|max:10',
            ];
        else
            return ['type'=>'required|in:trip,event,flight'];
    }
}
