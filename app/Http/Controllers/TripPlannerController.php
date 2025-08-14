<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIChatService;
use Illuminate\Support\Facades\Log;

class TripPlannerController extends Controller
{
    public function generateTrip(Request $request, AIChatService $aiService)
    {
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

        if (!$result['success']) {
            $status = match ($result['error'] ?? '') {
                'AI service error' => 503,
                'Empty AI response' => 500,
                'Invalid JSON format' => 422,
                default => 500
            };

            return response()->json($result, $status);
        }

        return response()->json([
            'success' => true,
            'Trip' => $result['Trip']
        ]);
    }
}
