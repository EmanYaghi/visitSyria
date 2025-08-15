<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EventResource extends JsonResource
{
    /**
     * Include status for admin endpoints or when ?include_status=1
     */
    protected function shouldIncludeStatus($request): bool
    {
        // query param takes precedence
        if ($request->query('include_status') === '1') {
            return true;
        }

        // check path segments for 'admin' (covers api/admin/... too)
        try {
            $segments = $request->segments();
            if (in_array('admin', $segments, true)) {
                return true;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // fallback: if path contains admin (conservative)
        try {
            $path = $request->path();
            if (!empty($path) && Str::contains($path, 'admin')) {
                return true;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return false;
    }

    /**
     * Compute dynamic status: not_started | ongoing | finished | cancelled
     * Respect persisted 'cancelled' flag in DB.
     */
    protected function computeStatus(): array
    {
        // if DB says cancelled -> cancelled
        if (!empty($this->status) && $this->status === 'cancelled') {
            return ['code' => 'cancelled', 'label' => 'تم الإلغاء'];
        }

        try {
            $now = Carbon::now();
            $start = null;

            if ($this->date instanceof Carbon) {
                $start = $this->date;
            } elseif (!empty($this->date)) {
                $start = Carbon::parse($this->date);
            }

            $days  = intval($this->duration_days ?? 0);
            $hours = intval($this->duration_hours ?? 0);

            if ($start) {
                $end = (clone $start)->addDays($days)->addHours($hours);

                if ($now->lt($start)) {
                    return ['code' => 'not_started', 'label' => 'لم تبدأ بعد'];
                } elseif ($now->gt($end)) {
                    return ['code' => 'finished', 'label' => 'منتهية'];
                } else {
                    return ['code' => 'ongoing', 'label' => 'جارية حالياً'];
                }
            }

            // no date -> default not started
            return ['code' => 'not_started', 'label' => 'لم تبدأ بعد'];
        } catch (\Throwable $e) {
            return ['code' => 'not_started', 'label' => 'لم تبدأ بعد'];
        }
    }

    /**
     * Convert local storage path to full URL
     */
    protected function toFullUrl(?string $raw): ?string
    {
        if (empty($raw)) return null;
        $raw = trim($raw);
        if (filter_var($raw, FILTER_VALIDATE_URL)) return $raw;

        $raw = preg_replace('#(events/)+#', 'events/', $raw);
        $raw = preg_replace('#(storage/)+#', 'storage/', $raw);

        if (Str::startsWith($raw, ['/storage', 'storage'])) {
            return url('/' . ltrim($raw, '/'));
        }

        try {
            $storageUrl = Storage::url($raw);
            if (filter_var($storageUrl, FILTER_VALIDATE_URL)) {
                return $storageUrl;
            }
            return url($storageUrl);
        } catch (\Throwable $e) {
            return url('/storage/' . ltrim($raw, '/'));
        }
    }

    public function toArray($request)
    {
        $this->loadMissing('media');

        $mediaUrls = collect($this->media ?? [])->map(function ($m) {
            $raw = $m->url ?? null;
            if (! $raw) return null;
            if (filter_var($raw, FILTER_VALIDATE_URL)) return $raw;
            return Storage::disk('public')->url(ltrim($raw, '/'));
        })->filter()->values()->all();

        $user = $request->user('api') ?? $request->user();
        if ($user) {
            $isSaved = (bool) ($this->relationLoaded('saves')
                ? $this->saves->contains('user_id', $user->id)
                : $this->saves()->where('user_id', $user->id)->whereNotNull('event_id')->exists());
        } else {
            $isSaved = null;
        }

        // --- هنا: date كـ YYYY-MM-DD فقط ---
        $dateValue = null;
        if ($this->date instanceof Carbon) {
            $dateValue = $this->date->toDateString(); // YYYY-MM-DD
        } elseif (!empty($this->date)) {
            try {
                $dateValue = Carbon::parse($this->date)->toDateString();
            } catch (\Throwable $e) {
                // fall back to raw value if parsing fails
                $dateValue = $this->date;
            }
        }

        $result = [
            'id'               => $this->id,
            'name'             => $this->name,
            'description'      => $this->description,
            'longitude'        => $this->longitude,
            'latitude'         => $this->latitude,
            'place'            => $this->place,
            'date'             => $dateValue,
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

        if ($this->shouldIncludeStatus($request)) {
            $status = $this->computeStatus();
            $result['status_code'] = $status['code'];
            $result['status'] = $status['label'];
        }

        return $result;
    }
}
