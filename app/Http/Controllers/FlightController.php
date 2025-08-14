<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlightSearchRequest;
use Illuminate\Http\Request;
use App\Services\AmadeusService;
use App\Http\Resources\FlightOfferResource;
use Illuminate\Support\Arr;

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
                'meta'   => $resp['meta'] ?? [],
                'offers' => collect([]),
                'error'  => $resp['error'] ?? null,
                'message'=> $resp['message'] ?? null,
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

    // ----- simple cases: only to_syria or from_syria -----
    if ($direction === 'to_syria') {
        if (empty($validated['originLocationCode'])) {
            return response()->json(['error' => true, 'message' => 'originLocationCode required for direction=to_syria'], 422);
        }
        $params = $validated;
        $params['destinationLocationCode'] = 'DAM';
        $mapped = $mapResponseToResources($doSearch($params));
        return response()->json(['offers' => ['inbound' => $mapped['offers']->values()]]);
    }

    if ($direction === 'from_syria') {
        if (empty($validated['destinationLocationCode'])) {
            return response()->json(['error' => true, 'message' => 'destinationLocationCode required for direction=from_syria'], 422);
        }
        $params = $validated;
        $params['originLocationCode'] = 'DAM';
        $mapped = $mapResponseToResources($doSearch($params));
        return response()->json(['offers' => ['outbound' => $mapped['offers']->values()]]);
    }

    // ----- both: try round-trip search first (flexible with origin/destination order) -----
    if ($direction === 'both') {
        // if returnDate provided, prefer a round-trip style search (but respect user's order if both origin & destination sent)
        if (!empty($validated['returnDate'])) {
            // determine origin/destination to send to Amadeus:
            // if user provided both, use them as-is (so LHR->DAM or DAM->LHR both work).
            // if only one side provided, assume the other is DAM (to support the "from/to Syria" UX).
            $origin = $validated['originLocationCode'] ?? null;
            $destination = $validated['destinationLocationCode'] ?? null;

            if (!$origin && !$destination) {
                return response()->json(['offers' => ['paired' => collect([]), 'unpaired' => collect([])]]);
            }

            if ($origin && $destination) {
                $params = $validated;
                $params['originLocationCode'] = $origin;
                $params['destinationLocationCode'] = $destination;
            } elseif ($destination && !$origin) {
                $params = $validated;
                $params['originLocationCode'] = 'DAM';
                $params['destinationLocationCode'] = $destination;
            } else {
                $params = $validated;
                $params['originLocationCode'] = $origin;
                $params['destinationLocationCode'] = 'DAM';
            }

            $resp = $doSearch($params);
            $mapped = $mapResponseToResources($resp);
            $offers = $mapped['offers'] ?? collect([]);

            $paired = $offers->filter(fn($o) => !empty($o['is_round_trip']))->values();
            $unpaired = $offers->filter(fn($o) => empty($o['is_round_trip']))->values();

            return response()->json([
                'offers' => [
                    'paired' => $paired,
                    'unpaired' => $unpaired,
                ],
            ]);
        }

        $outboundResult = ['offers' => collect([])];
        $inboundResult  = ['offers' => collect([])];

        if (!empty($validated['destinationLocationCode'])) {
            $outParams = $validated;
            $outParams['originLocationCode'] = 'DAM';
            $outParams['destinationLocationCode'] = $validated['destinationLocationCode'];
            $outboundResult = $mapResponseToResources($doSearch($outParams));
        }

        if (!empty($validated['originLocationCode'])) {
            $inParams = $validated;
            $inParams['destinationLocationCode'] = 'DAM';
            $inParams['originLocationCode'] = $validated['originLocationCode'];
            $inboundResult = $mapResponseToResources($doSearch($inParams));
        }

        $outOffers = $outboundResult['offers'] ?? collect([]);
        $inOffers  = $inboundResult['offers'] ?? collect([]);

        if ($outOffers->isEmpty() || $inOffers->isEmpty()) {
            return response()->json([
                'offers' => [
                    'paired' => collect([]),
                    'unpaired_outbound' => $outOffers->values(),
                    'unpaired_inbound'  => $inOffers->values(),
                ],
            ]);
        }

        // pairing heuristic (price/class/stops)
        $inList = $inOffers->values()->all();
        $usedIn = [];
        $paired = collect();

        foreach ($outOffers->values()->all() as $outOffer) {
            $bestIdx = null;
            $bestScore = null;

            foreach ($inList as $iIdx => $inOffer) {
                if (in_array($iIdx, $usedIn, true)) continue;

                $outP = isset($outOffer['price_total']) ? (float)$outOffer['price_total'] : null;
                $inP  = isset($inOffer['price_total'])  ? (float)$inOffer['price_total']  : null;
                $priceDiff = ($outP !== null && $inP !== null) ? abs($outP - $inP) : 1000000;

                $classPenalty = 0;
                if (!empty($travelClass)) {
                    $outClass = $outOffer['travel_class'] ?? null;
                    $inClass  = $inOffer['travel_class'] ?? null;
                    if ($outClass && $inClass && $outClass !== $inClass) $classPenalty += 5000;
                }

                $outStops = $outOffer['stops'] ?? 0;
                $inStops  = $inOffer['stops'] ?? 0;
                $stopsPenalty = abs($outStops - $inStops) * 100;

                $score = $priceDiff + $classPenalty + $stopsPenalty;

                if ($bestScore === null || $score < $bestScore) {
                    $bestScore = $score;
                    $bestIdx = $iIdx;
                }
            }

            if ($bestIdx !== null) {
                $matched = $inList[$bestIdx];
                $usedIn[] = $bestIdx;

                $combinedPrice = null;
                if (isset($outOffer['price_total']) && isset($matched['price_total'])) {
                    $combinedPrice = (float)$outOffer['price_total'] + (float)$matched['price_total'];
                }

                $paired->push([
                    'outbound' => $outOffer,
                    'inbound'  => $matched,
                    'combined_price' => $combinedPrice,
                    'currency' => $outOffer['currency'] ?? $matched['currency'] ?? null,
                    'pair_score' => $bestScore,
                ]);
            }
        }

        $pairedOutIds = $paired->pluck('outbound')->pluck('id')->all();
        $pairedInIds  = $paired->pluck('inbound')->pluck('id')->all();

        $unpairedOut = $outOffers->filter(fn($o) => !in_array($o['id'] ?? null, $pairedOutIds))->values();
        $unpairedIn  = $inOffers->filter(fn($o) => !in_array($o['id'] ?? null, $pairedInIds))->values();

        return response()->json([
            'offers' => [
                'paired' => $paired->values(),
                'unpaired_outbound' => $unpairedOut,
                'unpaired_inbound'  => $unpairedIn,
            ],
        ]);
    } // end both

    // ----- default single search (legacy) -----
    $params = $validated;
    $resp = $this->flightService->searchFlights($params);
    $single = $mapResponseToResources($resp);

    return response()->json([
        'offers' => [
            'outbound' => $single['offers']->values(),
            'inbound'  => collect([]),
            'timeline' => $single['offers']->values()->sortBy('departure_timestamp')->values(),
        ],
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
}
