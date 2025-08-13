<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightSearchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // تحويل nonStop لقيمة Boolean حقيقية أو null إذا لم تُرسل
        if ($this->has('nonStop')) {
            $this->merge([
                'nonStop' => filter_var($this->nonStop, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function rules()
    {
        return [
            'direction' => 'nullable|string',
            'originLocationCode' => 'required|string|size:3',
            'destinationLocationCode' => 'required|string|size:3',
            'departureDate' => 'required|date',
            'returnDate' => 'nullable|date',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'infants' => 'nullable|integer|min:0',
            'travelClass' => 'nullable|in:ECONOMY,BUSINESS,FIRST,PREMIUM_ECONOMY',
            'nonStop' => 'nullable|boolean',
            'currencyCode' => 'nullable|string|size:3',
            'max' => 'nullable|integer|min:1|max:250',
        ];
    }
}
