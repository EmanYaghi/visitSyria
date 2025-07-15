<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CreateAdminProfileRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name_of_company'=>'required|string|max:255',
            'name_of_owner'=>'required|string|max:255',
            'founing_date'=>'required|date',
            'license_number'=>'required|string',
            'phone'=>'required|string',
            'country_code'=>'required',
            'description'=>'required|string',
            'location'=>'required|numaric',
            'latitude'=>'required|numaric',
            'longitude'=>'required|string'
        ];
    }
}
