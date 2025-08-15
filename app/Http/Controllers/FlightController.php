<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookFlightRequest;
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
            if (isset($params['returnDate'])) unset($params['returnDate']);

            $mapped = $mapResponseToResources($doSearch($params));

            $oneWays = $mapped['offers']->filter(fn($o) => empty($o['is_round_trip']))->values();

            return response()->json(['offers' => $oneWays]);
        }

        // ------------------- from_syria -------------------
        if ($direction === 'from_syria') {
            if (empty($validated['destinationLocationCode'])) {
                return response()->json(['error' => true, 'message' => 'destinationLocationCode required for direction=from_syria'], 422);
            }

            $params = $validated;
            $params['originLocationCode'] = 'DAM';
            if (isset($params['returnDate'])) unset($params['returnDate']);

            $mapped = $mapResponseToResources($doSearch($params));

            $oneWays = $mapped['offers']->filter(fn($o) => empty($o['is_round_trip']))->values();

            return response()->json(['offers' => $oneWays]);
        }

        // ------------------- both -------------------
        if ($direction === 'both') {
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

            $roundTrips = $mapped['offers']->filter(fn($o) => !empty($o['is_round_trip']))->values();

            $result = $roundTrips->map(function($o) {
                if (empty($o['outbound']) || empty($o['inbound'])) return null;

                $priceTotal = $o['price_total'] ?? null;
                $travCount = $o['traveler_count'] ?? 0;
                $pricePerPassenger = ($priceTotal !== null && $travCount > 0) ? round($priceTotal / $travCount, 2) : null;

                return [
                    'id' => $o['id'] ?? null,
                    'is_round_trip' => true,
                    'price_total' => $priceTotal,
                    'currency' => $o['currency'] ?? null,
                    'traveler_count' => $travCount,
                    'price_per_passenger' => $pricePerPassenger,
                    'seats_remaining' => $o['seats_remaining'] ?? null,
                    'departure' => $o['outbound'],
                    'return'    => $o['inbound'],
                ];
            })->filter()->values();

            return response()->json(['offers' => $result]);
        }

        $params = $validated;
        $resp = $this->flightService->searchFlights($params);
        $single = $mapResponseToResources($resp);

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

    public function bookFlight(BookFlightRequest $request)
    {
        $data = $this->flightService->bookFlight($request->validated());
        return response()->json(['message' => $data['message'], 'booking' => $data['booking'] ?? null], $data['code']);
    }
}
