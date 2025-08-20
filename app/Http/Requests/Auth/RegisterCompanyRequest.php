<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'email'=>'required|string|email|max:255|unique:users,email',
            'name_of_company'=>'required|string|max:255',
            'name_of_owner'=>'required|string|max:255',
            'founding_date'=>'required|date',
            'license_number'=>'required|string',
            'phone'=>'required|string',
            'country_code'=>'required',
            'description'=>'required|string',
            'location'=>'nullable',
            'latitude'=>'required',
            'longitude'=>'required|string',
            'image'=>'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'documents'=>'required|array|min:1|max:10',
            'documents.*'=>'image|mimes:jpeg,png,jpg|max:2048'
        ];
    }
}
