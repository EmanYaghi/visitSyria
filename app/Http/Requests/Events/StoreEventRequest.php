<?php
namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->hasRole('super_admin');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'place'=>'required|string',
            'date' => 'required|date',
            'duration_days' => 'required|integer',
            'duration_hours' => 'required|integer',
            'tickets' => 'required|integer',
            'price' => 'required|numeric',
            'event_type' => 'required|in:limited,unlimited',
            'price_type' => 'required|in:free,paid',
            'pre_booking' => 'required|boolean',
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ];
    }
}
