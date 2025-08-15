<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupportStoreRequest extends FormRequest
{
    public function authorize()
    {
        // السماح هنا، فالحماية تتم عبر middleware 'auth:api'
        return true;
    }

    public function rules()
    {
        return [
            'rating' => 'nullable|integer|min:0|max:5',
            'comment' => 'nullable|string|max:2000',
            'category' => 'sometimes|string|in:app,admin',
        ];
    }
}
