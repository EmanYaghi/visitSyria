<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class companyWithEarningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $companyId=$this->id;
        $user=$this->user;
         return [
            'user' =>$user,
            'id' => $this->id,
            'name_of_company' => $this->name_of_company,
            'name_of_owner' => $this->name_of_owner,
            'founding_date' => $this->founding_date,
            'license_number' => $this->license_number,
            'image' =>  $this->image ? asset('storage/' . $this->image) : null,
            'documents' => ($user && $user->media())?$user->media()->get()->map(fn($media) => asset('storage/' . $media->url)):[],
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'description' => $this->description??null,
            'location' => $this->location??null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'number_of_trips' => $this->number_of_trips??null,
            'rating' =>$this->rating,
            'earning' => User::where('id', $user->id)
                ->with('trips.bookings')
                ->first()
                ?->trips->sum(fn($trip) => $trip->bookings->where('is_paid', true)->sum('price') / 5) ?? 0,

        ];
    }
}
