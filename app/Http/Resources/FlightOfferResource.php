<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FlightOfferResource extends JsonResource
{
   public function toArray($request)
{
    $itinerary = $this->itineraries[0] ?? null;
    $segments = $itinerary['segments'] ?? [];
    $first = $segments[0] ?? null;

    return [
        'origin' => $first['departure']['iataCode'] ?? null,
        'destination' => $first['arrival']['iataCode'] ?? null,
        'departure_at' => $first['departure']['at'] ?? null,
        'arrival_at' => $first['arrival']['at'] ?? null,
        'duration' => $itinerary['duration'] ?? null,
        'stops' => max(count($segments) - 1, 0),
        'price_total' => $this->price['total'] ?? null,
        'currency' => $this->price['currency'] ?? null,
    ];
}

}
