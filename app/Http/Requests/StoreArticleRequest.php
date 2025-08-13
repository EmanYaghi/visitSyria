<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\TagName;

class StoreArticleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'body'  => 'required|string|min:10',
            'image' => 'sometimes|nullable|file|image|max:5120',
            'tags'  => 'sometimes|array|max:5',
            'tags.*'=> ['string', Rule::in(TagName::$article)],
        ];
    }
}
