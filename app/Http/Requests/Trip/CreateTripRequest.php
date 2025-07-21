<?php

namespace App\Http\Requests\Trip;

use App\Models\TagName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {

        return [
            'name'=>'required|string',
            'description'=>'required|string',
            'season'=>'required|in:الصيف,الخريف,الشتاء,الربيع',
            'start_date'=>'required|date',
            'duration'=>'required|string',
            'tickets'=>'required',
            'price'=>'required',
            'new_price'=>'nullable',
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
            'timelines.*.sections.*.description' => 'nullable|string',

        ];
    }
}
