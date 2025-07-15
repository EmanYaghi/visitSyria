<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CreateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'first_name'=>'required|string|max:255',
            'last_name'=>'required|string|max:255',
            'date_of_birth'=>'nullable|date',
            'gender'=>'nullable|string',
            'country'=>'required|string|max:255',
            'phone'=>'nullable|string',
            'country_code'=>'nullable|string',
            'photo'=>'nullable'
        ];
    }
}
