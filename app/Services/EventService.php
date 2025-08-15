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

    /**
     * Public listing: do NOT return cancelled events.
     */
    public function getAllEvents()
    {
        $events = $this->eventRepository->getAll();
        // remove cancelled for public listing
        $events = $events->filter(fn($e) => ($e->status !== 'cancelled'));

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
                $event->is_saved = (bool) ($event->relationLoaded('saves')
                    ? $event->saves->isNotEmpty()
                    : $event->saves()->where('user_id', $user->id)->whereNotNull('event_id')->exists());
            }
            return $event;
        })->values();
    }

    /**
     * Admin listing: return ALL events including cancelled.
     */
    public function getAllEventsForAdmin()
    {
        $this->checkAuthorization();

        $events = $this->eventRepository->getAll();

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
                $event->is_saved = (bool) ($event->relationLoaded('saves')
                    ? $event->saves->isNotEmpty()
                    : $event->saves()->where('user_id', $user->id)->whereNotNull('event_id')->exists());
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

        $data = $request->validated();
        // ensure DB only stores active/cancelled — default to active for new events
        if (empty($data['status'])) {
            $data['status'] = 'active';
        } else {
            $data['status'] = ($data['status'] === 'cancelled') ? 'cancelled' : 'active';
        }

        // handle images
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

        $event = $this->eventRepository->create($data);

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

        $data = $request->validated();
        // Do not change status unless explicitly provided; when provided, normalize to active/cancelled
        if (isset($data['status'])) {
            $data['status'] = ($data['status'] === 'cancelled') ? 'cancelled' : 'active';
        }

        $updatedEvent = $this->eventRepository->update($event, $data);

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

    /**
     * Cancel event: persist 'cancelled' into DB.
     */
    public function cancelEvent($id)
    {
        $this->checkAuthorization();

        try {
            $event = $this->eventRepository->find($id);
        } catch (\Throwable $e) {
            throw new NotFoundHttpException('Event not found.');
        }

        return $this->eventRepository->updateStatus($event, 'cancelled');
    }
}
