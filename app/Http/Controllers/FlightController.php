<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlightSearchRequest;
use Illuminate\Http\Request;
use App\Services\AmadeusService;
use App\Http\Resources\FlightOfferResource;
use Illuminate\Support\Arr;
use App\Models\Booking;

class FlightController extends Controller
{
    protected AmadeusService $flightService;
    public function __construct(AmadeusService $flightService)
    {
        $this->flightService = $flightService;
    }

    /**
     * Clean itinerary/object keys we don't want to expose in departure/return
     */
    protected function cleanItineraryArray(array $it): array
    {
        // remove the iso/timestamp fields if present
        unset($it['departure_datetime_iso'], $it['departure_timestamp']);
        return $it;
    }

    public function search(FlightSearchRequest $request)
    {
        $validated = $request->validated();
        $direction = $request->input('direction'); // 'from_syria'|'to_syria'|'both'|null
        $travelClass = $request->input('travelClass');

        $mapResponseToResources = function ($resp) use ($travelClass, $request) {
            if (empty($resp) || !empty($resp['error'])) {
                return [
                    'meta' => $resp['meta'] ?? [],
                    'offers' => collect([]),
                    'error' => $resp['error'] ?? null,
                    'message' => $resp['message'] ?? null,
                ];
            }

            $carriers  = $resp['dictionaries']['carriers'] ?? [];
            $locations = $resp['dictionaries']['locations'] ?? [];

            $offers = collect($resp['data'] ?? [])->map(function ($offer) use ($carriers, $locations, $travelClass, $request) {
                $resource = new FlightOfferResource($offer, $carriers, $locations, $travelClass);
                return $resource->toArray($request);
            });

            return [
                'meta' => $resp['meta'] ?? [],
                'offers' => $offers,
            ];
        };

        $doSearch = function(array $params) {
            return $this->flightService->searchFlights($params);
        };

        // ------------------- to_syria -------------------
        if ($direction === 'to_syria') {
            if (empty($validated['originLocationCode'])) {
                return response()->json(['error' => true, 'message' => 'originLocationCode required for direction=to_syria'], 422);
            }

            $params = $validated;
            $params['destinationLocationCode'] = 'DAM';
            // force one-way behavior: remove returnDate
            if (isset($params['returnDate'])) unset($params['returnDate']);

            $mapped = $mapResponseToResources($doSearch($params));
            // only one-way offers
            $oneWays = $mapped['offers']->filter(fn($o) => empty($o['is_round_trip']))->values();

            // wrap each into { departure, return: null } and clean unwanted keys
            $result = $oneWays->map(function($o) {
                // the one-way resource returns top-level itinerary fields (not outbound/inbound)
                $departure = $o;
                // if resource used 'outbound' key unexpectedly, prefer it
                if (!empty($o['outbound']) && is_array($o['outbound'])) {
                    $departure = $o['outbound'];
                }
                // clean
                $departure = $this->cleanItineraryArray($departure);

                return [
                    'id' => $o['id'] ?? null,
                    'is_round_trip' => false,
                    'price_total' => $o['price_total'] ?? null,
                    'currency' => $o['currency'] ?? null,
                    'traveler_count' => $o['traveler_count'] ?? null,
                    'price_per_passenger' => $o['price_per_passenger'] ?? null,
                    'seats_remaining' => $o['seats_remaining'] ?? null,
                    'departure' => $departure,
                    'return' => null,
                ];
            })->values();

            return response()->json(['offers' => $result]);
        }

        // ------------------- from_syria -------------------
        if ($direction === 'from_syria') {
            if (empty($validated['destinationLocationCode'])) {
                return response()->json(['error' => true, 'message' => 'destinationLocationCode required for direction=from_syria'], 422);
            }

            $params = $validated;
            $params['originLocationCode'] = 'DAM';
            // force one-way behavior: remove returnDate
            if (isset($params['returnDate'])) unset($params['returnDate']);

            $mapped = $mapResponseToResources($doSearch($params));
            $oneWays = $mapped['offers']->filter(fn($o) => empty($o['is_round_trip']))->values();

            $result = $oneWays->map(function($o) {
                $departure = $o;
                if (!empty($o['outbound']) && is_array($o['outbound'])) {
                    $departure = $o['outbound'];
                }
                $departure = $this->cleanItineraryArray($departure);

                return [
                    'id' => $o['id'] ?? null,
                    'is_round_trip' => false,
                    'price_total' => $o['price_total'] ?? null,
                    'currency' => $o['currency'] ?? null,
                    'traveler_count' => $o['traveler_count'] ?? null,
                    'price_per_passenger' => $o['price_per_passenger'] ?? null,
                    'seats_remaining' => $o['seats_remaining'] ?? null,
                    'departure' => $departure,
                    'return' => null,
                ];
            })->values();

            return response()->json(['offers' => $result]);
        }

        // ------------------- both -------------------
        if ($direction === 'both') {
            // require returnDate to get round-trip offers from Amadeus
            if (empty($validated['returnDate'])) {
                return response()->json(['offers' => collect([])]);
            }

            $params = $validated;
            if (empty($params['originLocationCode']) && !empty($params['destinationLocationCode'])) {
                $params['originLocationCode'] = 'DAM';
            }
            if (empty($params['destinationLocationCode']) && !empty($params['originLocationCode'])) {
                $params['destinationLocationCode'] = 'DAM';
            }

            $mapped = $mapResponseToResources($doSearch($params));
            // keep only offers that Resource marked as round-trip
            $roundTrips = $mapped['offers']->filter(fn($o) => !empty($o['is_round_trip']))->values();

            $result = $roundTrips->map(function($o) {
                // prefer outbound/inbound keys from resource
                $departure = $o['outbound'] ?? null;
                $return    = $o['inbound'] ?? null;

                if ($departure && is_array($departure)) $departure = $this->cleanItineraryArray($departure);
                if ($return && is_array($return)) $return = $this->cleanItineraryArray($return);

                return [
                    'id' => $o['id'] ?? null,
                    'is_round_trip' => true,
                    'price_total' => $o['price_total'] ?? null,
                    'currency' => $o['currency'] ?? null,
                    'traveler_count' => $o['traveler_count'] ?? null,
                    'price_per_passenger' => $o['price_per_passenger'] ?? null,
                    'seats_remaining' => $o['seats_remaining'] ?? null,
                    'departure' => $departure,
                    'return'    => $return,
                ];
            })->filter()->values();

            return response()->json(['offers' => $result]);
        }

        // ------------------- default generic single search (legacy) -------------------
        $params = $validated;
        $resp = $this->flightService->searchFlights($params);
        $single = $mapResponseToResources($resp);

        // return raw mapped offers (keeps existing behaviour)
        return response()->json([
            'offers' => $single['offers']->values(),
        ]);
    }

    public function searchLocation(Request $request)
    {
        $request->validate(['keyword' => 'required|string|min:2']);

        $response = $this->flightService->searchLocation($request->keyword);

        if (!empty($response['error'])) {
            return response()->json($response, 200);
        }

        $locations = collect($response['data'] ?? [])->map(function ($loc) {
            return [
                'name'       => ucfirst(strtolower($loc['name'] ?? '')),
                'iataCode'   => $loc['iataCode'] ?? '',
                'country'    => ucfirst(strtolower($loc['address']['countryName'] ?? '')),
                'type'       => strtolower($loc['subType'] ?? ''),
            ];
        });

        return response()->json($locations);
    }
<<<<<<< HEAD

    public function bookFlight(BookFlightRequest $request)
    {
        $data = $this->flightService->bookFlight($request->validated());
        return response()->json(['message' => $data['message'], 'booking' => $data['booking'] ?? null], $data['code']);
    }
=======
>>>>>>> origin/per
}
