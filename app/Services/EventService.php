<?php

namespace App\Services;

use App\Repositories\EventRepository;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Carbon\Carbon;

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
    $now = Carbon::now();
    $events = $events->filter(function ($e) use ($now) {
        if (!empty($e->status) && $e->status === 'cancelled') {
            return false;
        }
        $start = null;
        try {
            if ($e->date instanceof Carbon) {
                $start = $e->date;
            } elseif (!empty($e->date)) {
                $start = Carbon::parse($e->date);
            }
        } catch (\Throwable $ex) {
            $start = null;
        }

        $days  = intval($e->duration_days ?? 0);
        $hours = intval($e->duration_hours ?? 0);

        if ($start) {
            $end = (clone $start)->addDays($days)->addHours($hours);
            if (! $now->lt($start)) {
                return false;
            }
        }

        $ticketsTotal = isset($e->tickets) && $e->tickets !== null ? (int) $e->tickets : null;
        $reserved = isset($e->reserved_tickets) ? (int) $e->reserved_tickets : 0;

        if ($ticketsTotal !== null) {
            $remaining = max($ticketsTotal - $reserved, 0);
            return $remaining > 0;
        }

        return true;
    });
    $tokenPresent = (bool) request()->bearerToken();
    $user = $tokenPresent ? auth('api')->user() : null;


    if ($user) {
        $events = $events->filter(function ($event) use ($user) {
            return ! $event->bookings()->where('user_id', $user->id)->exists();
        })->values();
    }

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
        if (empty($data['status'])) {
            $data['status'] = 'active';
        } else {
            $data['status'] = ($data['status'] === 'cancelled') ? 'cancelled' : 'active';
        }

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
    if (isset($data['status'])) {
        $data['status'] = ($data['status'] === 'cancelled') ? 'cancelled' : 'active';
    }

    $updatedEvent = $this->eventRepository->update($event, $data);

    $updatedEvent->load('media');

    $oldImagesInput = $request->input('old_images', []);
    $oldImagesInput = is_array($oldImagesInput) ? array_filter(array_map('trim', $oldImagesInput)) : [];

    $kept = [];
    foreach ($updatedEvent->media as $media) {
        $rawPath = (string) $media->url;
        $storageUrl = null;
        try {
            $storageUrl = Storage::disk('public')->url(ltrim($rawPath, '/'));
        } catch (\Throwable $e) {
            $storageUrl = null;
        }
        $appUrlVariant = url('/' . ltrim($rawPath, '/'));
        $basename = pathinfo($rawPath, PATHINFO_BASENAME);

        $shouldKeep = false;
        foreach ($oldImagesInput as $old) {
            if ($old === '') continue;
            if ($old === $rawPath) {
                $shouldKeep = true;
                break;
            }
            if ($storageUrl && $old === $storageUrl) {
                $shouldKeep = true;
                break;
            }
            if ($old === $appUrlVariant) {
                $shouldKeep = true;
                break;
            }
            if (basename($old) === $basename) {
                $shouldKeep = true;
                break;
            }
        }

        if ($shouldKeep) {
            $kept[] = $media;
        }
    }

    foreach ($updatedEvent->media as $media) {
        $isKept = false;
        foreach ($kept as $k) {
            if ($k->id === $media->id) {
                $isKept = true;
                break;
            }
        }
        if (! $isKept) {
            try {
                Storage::disk('public')->delete($media->url);
            } catch (\Throwable $e) {

            }
            $media->delete();
        }
    }

    $currentKeptCount = count($kept);
    $maxTotal = 4;
    $allowedNew = max($maxTotal - $currentKeptCount, 0);

    if ($request->hasFile('images')) {
        $images = $request->file('images');
        if (count($images) > $allowedNew) {
            throw new \InvalidArgumentException("Cannot upload more than {$allowedNew} new image(s). Total images cannot exceed {$maxTotal}.");
        }

        foreach ($images as $image) {
            $path = $image->store('events', 'public');
            $updatedEvent->media()->create([
                'event_id' => $updatedEvent->id,
                'url' => $path,
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
