<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile=$this->profile;
        return [
            'id'=>$this->id,
            'email'=>$this->email,
            'first_name' => $profile->first_name,
            'last_name' => $profile->last_name,
            'photo' =>  $profile->photo ? asset('storage/' . $this->photo) : null,
            'date_of_birth' => $profile->date_of_birth,
            'gender' => $profile->gender,
            'country' => $profile->country,
            'phone' => $profile->phone,
            'country_code' => $profile->country_code,
            'preference'=>$this->preference??null,
            'reserved_trips'=> $this->bookings()->whereNotNull('trip_id')->count(),
            'reserved_events'=> $this->bookings()->whereNotNull('event_id')->count(),
            'number_of_post' => $this->posts()->count(),
            'status'=>$profile->account_status
        ];
    }
}
