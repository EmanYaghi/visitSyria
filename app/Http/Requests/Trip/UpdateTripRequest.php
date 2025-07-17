<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{

    public function authorize(): bool
    {
        return false;
    }
    public function rules(): array
    {
        return [
            'name'=>'somtimes|string',
            'description'=>'somtimes|string',
            'season'=>'sometimes|in:الصيف,الخريف,الشتاء,الربيع',
            'start_date'=>'somtimes|date',
            'duration'=>'somtimes|string',
            'tickets'=>'somtimes|numaric',
            'price'=>'somtimes|numaric',
            'discount'=>'somtimes|numaric',
            'new_price'=>'somtimes|numaric'
        ];
    }
}
