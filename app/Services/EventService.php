<?php

namespace App\Services;

use App\Http\Requests\UpdateEventRequest;
use App\Repositories\EventRepository;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventService
{
    protected $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    protected function checkAuthorization()
    {
        if (! Auth::user()->hasRole('super_admin')) {
            throw new UnauthorizedHttpException('', 'Unauthorized action.');
        }
    }

    public function getAllEvents()
    {
        $events = $this->eventRepository->getAll()->load('media');
        return $events->map(function ($event) {
            $event->setRelation('media', $event->media->map(fn($m) => [
                'id'  => $m->id,
                'url' => $m->url,
            ]));
            return $event;
        });
    }

    public function getEventById($id)
    {
        $event = $this->eventRepository->find($id);
        if (! $event) {
            throw new NotFoundHttpException('Event not found.');
        }
        $event->load('media');
        $event->setRelation('media', $event->media->map(fn($m) => [
            'id'  => $m->id,
            'url' => $m->url,
        ]));
        return $event;
    }

    public function createEvent(Request $request)
    {
        $this->checkAuthorization();
        $imageUrls = [];
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            if (count($images) > 4) {
                return response()->json(['message' => 'Cannot upload more than 4 images.'], 400);
            }
            foreach ($images as $image) {
                $imageUrls[] = $image->store('events', 'public');
            }
        }
        $event = $this->eventRepository->create($request->validated());
        foreach ($imageUrls as $url) {
            $event->media()->create([
                'event_id' => $event->id,
                'url'      => $url,
            ]);
        }
        $event->load('media');
        $event->setRelation('media', $event->media->map(fn($m) => [
            'id'  => $m->id,
            'url' => $m->url,
        ]));
        return $event;
    }
public function updateEvent(UpdateEventRequest $request, $id)
{
    $this->checkAuthorization();

    $event = $this->eventRepository->find($id);
    if (! $event) {
        throw new NotFoundHttpException('Event not found.');
    }

    $updatedData = $request->validated();

    $updatedEvent = $this->eventRepository->update($event, $updatedData);

    if ($request->hasFile('images')) {
        $images = $request->file('images');
        if (count($images) > 4) {
            throw new \Exception('Cannot upload more than 4 images.');
        }
        foreach ($images as $image) {
            $url = $image->store('events', 'public');
            $updatedEvent->media()->create([
                'event_id' => $updatedEvent->id,
                'url' => $url,
            ]);
        }
    }

    $updatedEvent->load('media');
    $updatedEvent->setRelation('media', $updatedEvent->media->map(fn($m) => [
        'id' => $m->id,
        'url' => $m->url,
    ]));

    return $updatedEvent;
}

public function deleteEvent($id)
    {
        $this->checkAuthorization();
        $event = $this->eventRepository->find($id);
        if (! $event) {
            throw new NotFoundHttpException('Event not found.');
        }
        $event->media->each(fn(Media $media) => tap($media, fn($m) => Storage::disk('public')->delete($m->url))->delete());
        return $this->eventRepository->delete($event);
    }
}
