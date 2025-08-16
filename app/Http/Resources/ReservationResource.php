<?php

namespace App\Http\Resources;

use App\Http\Resources\Trip\TripResource;
use App\Http\Resources\EventResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->trip_id ? 'trip' : ($this->event_id ? 'event' : 'flight');
        return [
            'info' => $type == 'trip'
                ? new TripResource($this->trip)
                : ($type == 'event'
                    ? new EventResource($this->event)
                    : $this->flight_data),

            'booking_info' => [
                'id' => $this->id ?? null,
                'number_of_tickets' => $this->number_of_tickets ?? null,
                'is_paid' => (bool)$this->is_paid ?? false,
                'price' => $this->price ?? null,
                'payment_status' => $this->payment_status ?? null,
                'passengers' => $this->passengers->map(function ($passenger) {
                    return [
                        'first_name' => $passenger->first_name,
                        'last_name' => $passenger->last_name,
                        'gender' => $passenger->gender,
                        'birth_date' => $passenger->birth_date,
                        'nationality' => $passenger->nationality,
                        'email' => $passenger->email,
                        'phone' => $passenger->phone,
                        'country_code' => $passenger->country_code,
                    ];
                }),
            ]
        ];
    }
}
