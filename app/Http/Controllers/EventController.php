<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use Illuminate\Http\Request;
use App\Http\Resources\EventResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventController extends Controller
{
    protected EventService $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function index(Request $request)
    {
        $events = $this->eventService->getAllEvents();

        if (is_array($events) && empty($events)) {
            return response()->json([], 200);
        }

        return EventResource::collection($events);
    }

    public function show(Request $request, $id)
    {
        try {
            $event = $this->eventService->getEventById($id);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        return new EventResource($event);
    }

    public function store(StoreEventRequest $request)
    {
        $event = $this->eventService->createEvent($request);
        return (new EventResource($event))->response()->setStatusCode(201);
    }

    public function update(UpdateEventRequest $request, $id)
    {
        try {
            $event = $this->eventService->updateEvent($request, $id);
            return new EventResource($event);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $this->eventService->deleteEvent($id);
        return response()->json(['message' => 'Event deleted successfully'], 200);
    }

    /**
     * Admin endpoint: return ALL events (including cancelled).
     */
    public function adminIndex(Request $request)
    {
        try {
            $events = $this->eventService->getAllEventsForAdmin();
        } catch (\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException $e) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Force include status in the resource (EventResource checks ?include_status=1)
        $request->merge(['include_status' => '1']);

        return EventResource::collection($events);
    }

    /**
     * Admin cancel endpoint: persist cancelled flag to DB.
     */
    public function cancel(Request $request, $id)
    {
        try {
            $event = $this->eventService->cancelEvent($id);
        } catch (\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException $e) {
            return response()->json(['message' => 'Forbidden'], 403);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        // ensure resource includes status
        $request->merge(['include_status' => '1']);

        return new EventResource($event);
    }
}
