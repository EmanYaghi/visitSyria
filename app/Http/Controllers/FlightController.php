<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookFlightRequest;
use App\Http\Requests\FlightSearchRequest;
use Illuminate\Http\Request;
use App\Services\AmadeusService;
use App\Http\Resources\FlightOfferResource;
use App\Models\Booking;

class FlightController extends Controller
{
    protected AmadeusService $flightService;

    public function __construct(AmadeusService $flightService)
    {
        $this->flightService = $flightService;
    }

    /**
     * Search flights depending on direction:
     * - from_syria : DAM -> destinationLocationCode
     * - to_syria   : originLocationCode -> DAM
     * - both       : return both outbound (DAM->dest) and inbound (origin->DAM) if possible
     *
     * Request uses the same validated fields from FlightSearchRequest.
     */
    public function search(FlightSearchRequest $request)
    {
        $validated = $request->validated();
        $direction = $request->input('direction'); // expect: 'from_syria' | 'to_syria' | 'both' (or null)
        $travelClass = $request->input('travelClass');

        // Helper to run a search and map offers to resources
        $mapResponseToResources = function ($resp) use ($travelClass, $request) {
            if (empty($resp) || !empty($resp['error'])) {
                return [
                    'meta'   => $resp['meta'] ?? [],
                    'offers' => collect([]),
                    'error'  => $resp['error'] ?? null,
                    'message'=> $resp['message'] ?? null,
                ];
            }

            $carriers  = $resp['dictionaries']['carriers'] ?? [];
            $locations = $resp['dictionaries']['locations'] ?? [];

            $offers = collect($resp['data'] ?? [])->map(function ($offer) use ($carriers, $locations, $travelClass) {
                return new FlightOfferResource($offer, $carriers, $locations, $travelClass);
            });

            return [
                'meta'   => $resp['meta'] ?? [],
                'offers' => $offers,
            ];
        };

        // Prepare containers
        $outboundResult = ['meta' => [], 'offers' => collect([])];
        $inboundResult  = ['meta' => [], 'offers' => collect([])];

        // normalize keys used by AmadeusService (your service expects originLocationCode/destinationLocationCode)
        // We'll clone validated each time and override only necessary fields
        if ($direction === 'to_syria') {
            // search flights TO Syria: origin supplied -> destination = DAM
            if (empty($validated['originLocationCode'])) {
                return response()->json([
                    'error' => true,
                    'message' => 'originLocationCode required for direction=to_syria'
                ], 422);
            }

            $params = $validated;
            $params['destinationLocationCode'] = 'DAM';
            // call search
            $resp = $this->flightService->searchFlights($params);
            $inboundResult = $mapResponseToResources($resp);

        } elseif ($direction === 'from_syria') {
            // search flights FROM Syria: origin = DAM -> destination supplied
            if (empty($validated['destinationLocationCode'])) {
                return response()->json([
                    'error' => true,
                    'message' => 'destinationLocationCode required for direction=from_syria'
                ], 422);
            }

            $params = $validated;
            $params['originLocationCode'] = 'DAM';
            $resp = $this->flightService->searchFlights($params);
            $outboundResult = $mapResponseToResources($resp);

        } elseif ($direction === 'both') {
            // both: attempt outbound (DAM -> destination) and inbound (origin -> DAM)
            // Outbound (DAM -> destination) if destinationLocationCode provided
            if (!empty($validated['destinationLocationCode'])) {
                $outParams = $validated;
                $outParams['originLocationCode'] = 'DAM';
                $outResp = $this->flightService->searchFlights($outParams);
                $outboundResult = $mapResponseToResources($outResp);
            }

            // Inbound (origin -> DAM) if originLocationCode provided
            if (!empty($validated['originLocationCode'])) {
                $inParams = $validated;
                $inParams['destinationLocationCode'] = 'DAM';
                $inResp = $this->flightService->searchFlights($inParams);
                $inboundResult = $mapResponseToResources($inResp);
            }

        } else {
            // default / legacy: do a single search with provided origin & destination (no special DAM override)
            // This keeps backward compatibility when no direction param is provided.
            $params = $validated;
            $resp = $this->flightService->searchFlights($params);
            $single = $mapResponseToResources($resp);

            // return same shape but populate outbound with single result (so caller still gets offers)
            return response()->json([
                'meta' => $single['meta'] ?? [],
                'offers' => $single['offers'],
            ]);
        }

        // Build final meta: include both counts/links if available
        $finalMeta = [
            'outbound_meta' => $outboundResult['meta'] ?? [],
            'inbound_meta'  => $inboundResult['meta'] ?? [],
        ];

        return response()->json([
            'meta' => $finalMeta,
            'offers' => [
                'outbound' => $outboundResult['offers']->values(), // reindex collections
                'inbound'  => $inboundResult['offers']->values(),
            ],
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

    public function bookFlight(BookFlightRequest $request)
    {
        $data = $this->flightService->bookFlight($request->validated());
        return response()->json(['message'=>$data['message'],'booking'=>$data['booking']??null],$data['code']);
    }
    public function ($id)
    {
        $booking=Booking::find(id);

        $data = $this->flightService->bookFlight($request->validated());
        return response()->json(['message'=>$data['message'],'booking'=>$data['booking']??null],$data['code']);
    }

}
