<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIChatService;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\ItineraryResource;
use App\Models\Itinerary;

class TripPlannerController extends Controller
{

    public function generateTrip(Request $request, AIChatService $aiService)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'type_of_trips' => 'nullable|array',
            'duration' => 'nullable|array',
            'average_activity' => 'nullable|array',
            'travel_with' => 'nullable|array',
            'sleeping_in_hotel' => 'nullable|array',
            'type_of_places' => 'nullable|array',
            'cities' => 'nullable|array',
        ]);

        $result = $aiService->generateTripItinerary($validated);

        if (!is_array($result) || empty($result['success'])) {
            $status = match ($result['error'] ?? '') {
                'AI service error' => 503,
                'Empty AI response' => 500,
                'Invalid JSON format' => 422,
                default => 500
            };

            return response()->json($result, $status);
        }

        $title = $result['title'] ?? null;
        $timelines = $result['timelines'] ?? [];
        $raw = $result['raw'] ?? null;
        $model = $result['model'] ?? env('HUGGINGFACE_MODEL');

        // Save to DB
        $itinerary = Itinerary::create([
            'user_id' => $user->id,
            'title' => $title,
            'request_payload' => $validated,
            'timelines' => $timelines,
            'raw_response' => $raw,
            'model' => $model,
        ]);

        return (new ItineraryResource($itinerary))
                ->response()
                ->setStatusCode(201);
    }

}
