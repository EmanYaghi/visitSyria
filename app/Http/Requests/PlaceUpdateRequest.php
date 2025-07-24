<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->hasRole('super_admin');
    }

    public function rules()
    {
        return [
            'city_name' => 'sometimes|exists:cities,name',
            'type' => 'sometimes|in:hotel,restaurant,tourist',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'number_of_branches' => 'nullable|integer|min:1',
            'phone' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|max:5',
            'place'=>'nullable|string',
            'longitude' => 'nullable|numeric|between:-180,180',
            'latitude' => 'nullable|numeric|between:-90,90',
            'rating' => 'nullable|numeric|between:0,5',
            'classification' => ['nullable', function($attr,$val,$fail){
                $t = $this->input('type');
                if(in_array($t,['hotel','restaurant']) && $val) $fail('Must be null for hotels/restaurants.');
                if($t==='tourist' && !in_array($val, ['ثقافية','تاريخية','دينية','ترفيهية','طبيعية','اثرية']))
                    $fail('Invalid classification for tourist.');
            }],
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ];
    }
}
