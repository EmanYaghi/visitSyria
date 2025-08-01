<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'first_name'=>'sometimes|string|max:255',
            'last_name'=>'sometimes|string|max:255',
            'date_of_birth'=>'sometimes|date',
            'gender'=>'sometimes|string',
            'country'=>'sometimes|string|max:255',
            'phone'=>'sometimes|string',
            'country_code'=>'required|string',
            'photo'=>'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'lang'=>'sometimes|string',
            'theme_mode'=>'sometimes|string',
            'allow_notification'=>'sometimes|string|max:255',
            'preferred_season'=>'sometimes|array',
            'preferred_activities'=>'sometimes|array',
            'duration'=>'sometimes|array',
            'cities'=>'sometimes|array'
        ];
    }
}
