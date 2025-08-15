<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'description' => 'nullable|string|max:2000',
            'tags'       => 'nullable|array|max:10',
            'tags.*'     => 'string|max:50',
            'image'      => 'nullable|image|max:2048', // 2MB
        ];
    }

    public function prepareForValidation()
    {
        // في حال أرسل المستخدم tags كسلسلة JSON أو كسلسلة مفصولة بفواصل، نتأكد أنها مصفوفة
        if ($this->has('tags') && !is_array($this->input('tags'))) {
            $tags = $this->input('tags');
            if (is_string($tags)) {
                // إذا JSON
                $decoded = json_decode($tags, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->merge(['tags' => $decoded]);
                } else {
                    // كـ comma-separated
                    $arr = array_filter(array_map('trim', explode(',', $tags)));
                    $this->merge(['tags' => $arr]);
                }
            }
        }
    }
}
