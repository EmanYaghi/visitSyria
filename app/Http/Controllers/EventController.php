<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;

class EventController extends Controller
{
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function index()
    {
        return response()->json($this->eventService->getAllEvents(), 200);
    }

    public function show($id)
    {
        $event = $this->eventService->getEventById($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }
        return response()->json(['data' => $event], 200);
    }

    public function store(StoreEventRequest $request)
    {
        $event = $this->eventService->createEvent($request);
        return response()->json(['data' => $event], 201);
    }
public function updateُ(UpdateEventRequest $request, $id)
{
    try {
        $event = $this->eventService->updateEvent($request, $id);
        return response()->json(['data' => $event], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 400);
    }
}


    public function destroy($id)
    {
        $this->eventService->deleteEvent($id);
        return response()->json(['message' => 'Event deleted successfully'], 200);
    }
}
