<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EventResource extends JsonResource
{
    public function toArray($request)
    {
        $this->loadMissing('media');

        $mediaUrls = $this->media->map(function ($m) {
            $raw = $m->url ?? null;
            if (! $raw) return null;
            if (filter_var($raw, FILTER_VALIDATE_URL)) return $raw;
            return Storage::disk('public')->url(ltrim($raw, '/'));
        })->filter()->values()->all();

        $isSaved = null;
        if (property_exists($this->resource, 'is_saved')) {
            $isSaved = $this->resource->is_saved;
        } else {
            $user = $request->user('api');
            if (! $user) {
                $isSaved = null;
            } else {
                $isSaved = (bool) ($this->relationLoaded('saves') ? $this->saves->contains('user_id', $user->id) : $this->saves()->where('user_id', $user->id)->whereNotNull('event_id')->exists());
            }
        }

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'description'      => $this->description,
            'longitude'        => $this->longitude,
            'latitude'         => $this->latitude,
            'place'            => $this->place,
            'date'             => $this->date,
            'duration_days'    => $this->duration_days,
            'duration_hours'   => $this->duration_hours,
            'tickets'          => $this->tickets,
            'reserved_tickets' => $this->reserved_tickets,
            'price'            => $this->price,
            'event_type'       => $this->event_type,
            'price_type'       => $this->price_type,
            'pre_booking'      => $this->pre_booking,
            'is_saved'         => $isSaved,
            'media'            => $mediaUrls,
            'created_at'       => $this->created_at?->toDateTimeString(),
            'updated_at'       => $this->updated_at?->toDateTimeString(),
        ];
    }
}
