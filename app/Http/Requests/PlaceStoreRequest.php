<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceStoreRequest extends FormRequest
{
    public function authorize() { 
        return auth()->user()->hasRole('super_admin'); 
    }
    public function rules(): array
    {
        return [
            'city_name'      => 'required|string|exists:cities,name',
            'type'           => 'required|in:hotel,restaurant,tourist',
            'name'           => 'required|string',
            'description'    => 'nullable|string',
            'number_of_branches'=>'nullable|integer|min:1',
            'phone'          => 'nullable|string',
            'country_code'   => 'nullable|string',
            'place'          => 'required|string',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'classification' => ['nullable', function($attr,$val,$fail){
                $t = $this->input('type');
                if(in_array($t,['hotel','restaurant']) && $val) $fail('Must be null for hotels/restaurants.');
                if($t==='tourist' && !in_array($val, ['ثقافية','تاريخية','دينية','ترفيهية','طبيعية','اثرية']))
                    $fail('Invalid classification for tourist.');
            }],
            'images'         => 'nullable|array|max:4',
            'images.*'       => 'image|max:2048',
        ];
    }
}
