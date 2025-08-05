<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlightSearchRequest;
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

    $offers = collect($response['data'])
        ->map(fn($offer) => new FlightOfferResource($offer));

    return response()->json([
        'meta' => $response['meta'] ?? [],
        'offers' => $offers,
    ]);
}

}
