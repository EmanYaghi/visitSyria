<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\TagName;

class UpdateArticleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'body'  => 'sometimes|required|string|min:10',
            'image' => 'sometimes|nullable|file|image|max:5120',
            'tags'  => 'sometimes|array|max:5',
            'tags.*'=> ['string', Rule::in(TagName::$article)],
        ];
    }
}
