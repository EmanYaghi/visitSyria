<?php

namespace App\Http\Resources\Trip;

use Illuminate\Http\Resources\Json\JsonResource;

class ReservationTripResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'=> $this->id,
            'name'=> $this->name,
            'description'=> $this->description,
            'season'=> $this->season,
            'start_date'=> $this->start_date,
            'duration'=> $this->duration,
            'price'=> $this->price,
        ];
    }
}
