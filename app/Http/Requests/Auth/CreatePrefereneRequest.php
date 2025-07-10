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
            'preferred_season'=>'nullable',
            'preferred_activities'=>'nullable',
            'duration'=>'nullable',
            'cities'=>'nullable'
        ];
    }
}
