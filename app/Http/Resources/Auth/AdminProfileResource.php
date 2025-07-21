<?php

namespace App\Http\Resources\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
         return [
            'user' => User::find($this->user_id),
            'id' => $this->id,
            'name_of_company' => $this->name_of_company,
            'name_of_owner' => $this->name_of_owner,
            'founding_date' => $this->founding_date,
            'license_number' => $this->license_number,
            'image' =>  $this->image ? asset('storage/' . $this->image) : null,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'description' => $this->description,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'number_of_trips' => $this->number_of_trips,
            'rating' => $this->rating,
        ];
    }
}
