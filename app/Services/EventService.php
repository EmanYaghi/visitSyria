<?php

namespace App\Services;

use App\Repositories\EventRepository;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventService
{
    protected EventRepository $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    protected function checkAuthorization(): void
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('super_admin')) {
            throw new UnauthorizedHttpException('', 'Unauthorized action.');
        }
    }

    public function getAllEvents()
    {
        $events = $this->eventRepository->getAll();
        $events->load('media');

        $user = auth('api')->user();

        if ($user) {
            $events->load(['saves' => function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereNotNull('event_id');
            }]);
        }

        return $events->map(function ($event) use ($user) {
            if (! $user) {
                $event->is_saved = null;
            } else {
                $event->is_saved = (bool) ($event->relationLoaded('saves') ? $event->saves->isNotEmpty() : $event->saves()->where('user_id', $user->id)->whereNotNull('event_id')->exists());
            }
            return $event;
        })->values();
    }

    public function getEventById($id)
    {
        try {
            $event = $this->eventRepository->findWithMedia($id);
        } catch (\Throwable $e) {
            throw new NotFoundHttpException('Event not found.');
        }

        $user = auth('api')->user();
        if ($user) {
            $event->load(['saves' => function ($q) use ($user) {
                $q->where('user_id', $user->id)->whereNotNull('event_id');
            }]);
            $event->is_saved = (bool) ($event->relationLoaded('saves') ? $event->saves->isNotEmpty() : $event->saves()->where('user_id', $user->id)->whereNotNull('event_id')->exists());
        } else {
            $event->is_saved = null;
        }

        return $event;
    }

    public function createEvent(Request $request)
    {
        $this->checkAuthorization();

        $imageUrls = [];
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            if (count($images) > 4) {
                throw new \InvalidArgumentException('Cannot upload more than 4 images.');
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

        return $this->eventRepository->findWithMedia($event->id);
    }

    public function updateEvent(Request $request, $id)
    {
        $this->checkAuthorization();

        $event = $this->eventRepository->find($id);
        if (! $event) {
            throw new NotFoundHttpException('Event not found.');
        }

        $updatedEvent = $this->eventRepository->update($event, $request->validated());

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            if (count($images) > 4) {
                throw new \InvalidArgumentException('Cannot upload more than 4 images.');
            }
            foreach ($images as $image) {
                $url = $image->store('events', 'public');
                $updatedEvent->media()->create([
                    'event_id' => $updatedEvent->id,
                    'url' => $url,
                ]);
            }
        }

        return $this->eventRepository->findWithMedia($updatedEvent->id);
    }

    public function deleteEvent($id)
    {
        $this->checkAuthorization();

        $event = $this->eventRepository->find($id);
        if (! $event) {
            throw new NotFoundHttpException('Event not found.');
        }

        $event->media->each(function (Media $media) {
            Storage::disk('public')->delete($media->url);
            $media->delete();
        });

        return $this->eventRepository->delete($event);
    }
}
