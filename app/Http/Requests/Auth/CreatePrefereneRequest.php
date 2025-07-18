<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CreatePrefereneRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'preferred_season'=>'nullable|array',
            'preferred_activities'=>'nullable|array',
            'duration'=>'nullable|array',
            'cities'=>'nullable|array'
        ];
    }
}
