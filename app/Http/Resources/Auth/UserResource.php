<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource->relationLoaded('profile') && $this->resource->profile) {
        $this->resource->profile->refreshIfUnblocked();
        $this->resource->load('profile');
    }

    $profile = $this->resource->profile;
    $remaining = $profile ? $profile->remainingUnblockInterval() : null;


        return [
            'id'                => $this->id,
            'email'             => $this->email,
            'first_name'        => $profile->first_name,
            'last_name'         => $profile->last_name,
            'photo'             => $profile->photo ? asset('storage/' . $profile->photo) : null,
            'date_of_birth'     => $profile->date_of_birth,
            'gender'            => $profile->gender,
            'country'           => $profile->country,
            'phone'             => $profile->phone,
            'country_code'      => $profile->country_code,
            'preference'        => $this->preference ?? null,
            'reserved_trips'    => $this->bookings()->whereNotNull('trip_id')->count(),
            'reserved_events'   => $this->bookings()->whereNotNull('event_id')->count(),
            'number_of_post'    => $this->posts()->count(),
            'account_status'    => $profile->account_status,
            'remaining_date_for_unblock' => $remaining,
        ];
    }
}
