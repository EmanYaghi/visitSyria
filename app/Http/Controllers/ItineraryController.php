<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Itinerary;
use App\Http\Resources\ItineraryResource;

class ItineraryController extends Controller
{
public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $requestedLimit = (int) $request->query('limit', 100);
        $limit = max(1, min($requestedLimit, 1000)); // default 100, max 1000

        $items = Itinerary::where('user_id', $user->id)
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get();

        return ItineraryResource::collection($items);
    }

    public function show(Request $request, Itinerary $itinerary)
    {
        $user = $request->user();
        if ($itinerary->user_id !== $user->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return new ItineraryResource($itinerary);
    }
}