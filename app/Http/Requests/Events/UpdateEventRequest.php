<?php
namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->hasRole('super_admin');
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'longitude' => 'sometimes|required|numeric',
            'latitude' => 'sometimes|required|numeric',
            'place' => 'sometimes|required|string',

            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',

            'old_images' => 'nullable|array|max:4',
            'old_images.*' => 'string',
        ];
    }
}
