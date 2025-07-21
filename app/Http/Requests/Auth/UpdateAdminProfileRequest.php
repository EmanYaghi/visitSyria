<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name_of_company'=>'sometimes|string|max:255',
            'name_of_owner'=>'sometimes|string|max:255',
            'founding_date'=>'sometimes|date',
            'license_number'=>'sometimes|string',
            'phone'=>'sometimes|string',
            'country_code'=>'sometimes|string',
            'description'=>'sometimes|string',
            'location'=>'sometimes|numaric',
            'latitude'=>'sometimes|numaric',
            'longitude'=>'sometimes|string',
            'image'=>'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ];
    }
}
