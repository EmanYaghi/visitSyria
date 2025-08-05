<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AmadeusService
{
protected function getAccessToken() {
    return Cache::remember('amadeus_token', 60, function() {
        $resp = Http::asForm()->post(config('services.amadeus.base_url').'/v1/security/oauth2/token', [
            'grant_type'    => 'client_credentials',
            'client_id'     => config('services.amadeus.client_id'),
            'client_secret' => config('services.amadeus.client_secret'),
        ]);

        if (!$resp->successful()) {
            throw new \Exception('Amadeus Token Error: ' . $resp->body());
        }

        $data = $resp->json();

        if (!isset($data['access_token'])) {
            throw new \Exception('No access_token in response: ' . json_encode($data));
        }

        return $data['access_token'];
    });
}

    public function searchFlights($origin, $dest, $date) {
        $token = $this->getAccessToken();
        $resp = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->get(config('services.amadeus.base_url').'/v2/shopping/flight-offers', [
            'originLocationCode'      => $origin,
            'destinationLocationCode' => $dest,
            'departureDate'           => $date,
            'adults'                  => 1,
            'max'                     => 5,      // عدد النتائج الأقصى
        ]);
        $data = $resp->json();
        $results = [];
        if (isset($data['data'])) {
            foreach ($data['data'] as $offer) {
                $itinerary = $offer['itineraries'][0];
                $segments  = $itinerary['segments'];
                $firstSeg = $segments[0];
                $lastSeg  = end($segments);

                $airlineCode = $firstSeg['carrierCode'];
                $airlineName = $data['dictionaries']['carriers'][$airlineCode] 
                               ?? $airlineCode;

                $results[] = [
                    'airline'          => $airlineName,
                    'departureAirport' => $firstSeg['departure']['iataCode'],
                    'arrivalAirport'   => $lastSeg['arrival']['iataCode'],
                    'departureTime'    => $firstSeg['departure']['at'],  // ISO تاريخ+وقت
                    'arrivalTime'      => $lastSeg['arrival']['at'],
                    'duration'         => $itinerary['duration'],        // مثال "PT6H10M"
                    'price'            => $offer['price']['grandTotal'] . ' ' . $offer['price']['currency'],
                ];
            }
        }
        return $results;
    }}