<?php

namespace App\Http\Resources\Trip;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user= User::find($this->user_id);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_date' => $this->start_date,
            'duration' => $this->duration,
            'tickets' => $this->tickets,
            'photos' =>  $this->photo ? asset('storage/' . $this->photo) : null,
            'reserved_tickets' => $this->reserved_tickets,
            'season' => $this->season,
            'description' => $this->description,
            'discount' => $this->discount,
            'price' => $this->price,
            'new_price' => $this->new_price,
            'tag' => $this->tag,
            'number_of_trips' => $this->number_of_trips,
            'improvements' => $this->improvements,
            'name_of_company' => $user->name,
            'photo_of_company' => $user->photo ?asset('storage/' . $user->photo) : null,
            'timelines' => $this->rating,
                    $user = User::with('images')->findOrFail($id);

        ];
    }
}
