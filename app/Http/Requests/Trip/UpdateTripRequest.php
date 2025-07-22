<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name'=>'sometimes|string',
            'description'=>'sometimes|string',
            'season'=>'sometimes',
            'start_date'=>'sometimes|date',
            'duration'=>'sometimes|string',
            'tickets'=>'sometimes',
            'price'=>'sometimes',
            'new_price'=>'sometimes',
            'improvements'=>'nullable|array',
            'improvements.*'=>'string',
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'tags'=>'nullable|array|max:10',
            'tags.*'=>['nullable'],
            'timelines' => 'nullable|array',
            'timelines.*.day' => 'nullable',
            'timelines.*.sections' => 'nullable|array',
            'timelines.*.sections.*.time' => 'nullable',
            'timelines.*.sections.*.title' => 'nullable|string',
            'timelines.*.sections.*.latitude' => 'nullable',
            'timelines.*.sections.*.longitude' => 'nullable',
            'timelines.*.sections.*.description' => 'nullable|array',
            'timelines.*.sections.*.description.*' => 'nullable',
        ];
    }
}
