<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightSearchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'origin'         => 'required|string|size:3',
            'destination'    => 'required|string|size:3',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date'    => 'nullable|date|after_or_equal:departure_date',
            'adults'         => 'required|integer|min:1',
            'children'       => 'nullable|integer|min:0',
            'infants'        => 'nullable|integer|min:0',
            'travel_class'   => 'nullable|in:ECONOMY,PREMIUM_ECONOMY,BUSINESS,FIRST',
            'non_stop'       => 'nullable',
            'max'            => 'nullable|integer|min:1|max:250',
        ];
    }
}
