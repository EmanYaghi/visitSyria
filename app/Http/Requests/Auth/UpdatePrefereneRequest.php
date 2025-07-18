<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrefereneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'preferred_season'=>'sometimes|array',
            'preferred_activities'=>'sometimes|array',
            'duration'=>'sometimes|array',
            'cities'=>'sometimes|array'
        ];
    }
}
