<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlightSearchRequest;
use Illuminate\Http\Request;
use App\Services\AmadeusService;
use App\Http\Resources\FlightOfferResource;

class FlightController extends Controller
{
    protected AmadeusService $flightService;

    public function __construct(AmadeusService $flightService)
    {
        $this->flightService = $flightService;
    }

    public function search(FlightSearchRequest $request)
    {
        $response = $this->flightService->searchFlights($request->validated());

        if (!empty($response['error'])) {
            return response()->json($response, 200);
        }

        $carriers  = $response['dictionaries']['carriers'] ?? [];
        $locations = $response['dictionaries']['locations'] ?? [];

        $offers = collect($response['data'])->map(function ($offer) use ($carriers, $locations, $request) {
            return new FlightOfferResource(
                $offer,
                $carriers,
                $locations,
                $request->input('travelClass')
            );
        });

        return response()->json([
            'meta'   => $response['meta'] ?? [],
            'offers' => $offers,
        ]);
    }
 public function searchLocation(Request $request)
{
    $request->validate([
        'keyword' => 'required|string|min:2',
    ]);

    $response = $this->flightService->searchLocation($request->keyword);

    if (!empty($response['error'])) {
        return response()->json($response, 200);
    }

    $locations = collect($response['data'] ?? [])->map(function ($loc) {
        return [
            'name'       => ucfirst(strtolower($loc['name'] ?? '')),
            'iataCode'   => $loc['iataCode'] ?? '',
            'country'    => ucfirst(strtolower($loc['address']['countryName'] ?? '')),
            'type'       => strtolower($loc['subType'] ?? ''), // airport أو city
        ];
    });

    return response()->json($locations);
}


}
