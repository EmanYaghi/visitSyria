<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => 'required|in:privacy_policy,support,common_question,about_app',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:app,admin,appandadmin',
        ];
    }
}
