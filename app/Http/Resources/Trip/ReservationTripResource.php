<?php

namespace App\Http\Resources\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "trip"=>new TripResource($request),
            "qr_code"=>$this->bookings->qr_code
        ];
    }
}
