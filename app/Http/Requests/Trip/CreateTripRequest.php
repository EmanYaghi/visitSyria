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
            'discount'=>'nullable',
            'new_price'=>'nullable',
            'improvements'=>'nullable|array',
            'improvements.*'=>'string',
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'tags'=>'nullable|array|max:10',
            'tags.*'=>['nullable'],
            'timeline' => 'nullable|array',
            'timeline.*.day' => 'nullable',
            'timeline.*.section' => 'nullable|array',
            'timeline.*.section.*.time' => 'nullable',
            'timeline.*.section.*.title' => 'nullable|string',
            'timeline.*.section.*.description' => 'nullable|string',

        ];
    }
}
