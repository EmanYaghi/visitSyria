<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\City;

class PlaceResource extends JsonResource
{
    protected function makePublicUrl(?string $raw): ?string
    {
        if (empty($raw)) return null;

        $raw = trim($raw);

        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            return $raw;
        }

        $raw = preg_replace('#^(public/)+#', '', $raw);
        $raw = preg_replace('#^(storage/)+#', 'storage/', $raw);
        $raw = ltrim($raw, '/');

        if (strpos($raw, 'storage/') === 0) {
            return url('/' . $raw);
        }

        try {
            $storageUrl = Storage::disk('public')->url($raw);
            if ($storageUrl && filter_var($storageUrl, FILTER_VALIDATE_URL)) {
                return $storageUrl;
            }
            if ($storageUrl) {
                return url($storageUrl);
            }
        } catch (\Throwable $e) {

        }

        return url('/storage/' . ltrim($raw, '/'));
    }

    public function toArray($request)
    {
        $this->loadMissing([
            'media',
            'ratings',
            'comments.user.profile',
            'comments.user.media',
            'latestComments.user.profile',
            'latestComments.user.media',
            'city',
        ]);

        $avgRating = (float) ($this->ratings()->avg('rating_value') ?: 0);
        $user = $request->user('api') ?? $request->user();
        $userId = $user ? $user->id : null;

        $userRating = $userId ? $this->ratings()->where('user_id', $userId)->value('rating_value') ?? null : null;
        $userComment = $userId ? $this->comments()->where('user_id', $userId)->value('body') ?? null : null;

        $mediaCollection = $this->relationLoaded('media') ? $this->media : $this->media()->get();

        $images = $mediaCollection->map(function ($m) {
            $raw = $m->url ?? ($m->path ?? ($m->file ?? null));
            return $this->makePublicUrl($raw);
        })->filter()->values()->all();

        if (array_key_exists('is_saved', $this->resource->getAttributes())) {
            $isSaved = $this->resource->is_saved;
        } else {
            $isSaved = $userId === null ? null : (bool) $this->saves()->where('user_id', $userId)->whereNotNull('place_id')->exists();
        }

        $latestComments = $this->relationLoaded('latestComments') ? $this->latestComments : null;
        if (! $latestComments || $latestComments->isEmpty()) {
            $latestComments = $this->comments()
                                   ->with(['user.profile', 'user.media'])
                                   ->latest()
                                   ->limit(3)
                                   ->get();
        } else {
            $latestComments->loadMissing(['user.profile', 'user.media']);
        }

        $recentComments = $latestComments->map(function ($comment) {
            $user = $comment->user;
            $profile = $user?->profile;
            $userName = $profile ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) : ($user?->name ?? null);

            $avatarRaw = null;
            if ($profile) {
                foreach (['avatar', 'image', 'photo', 'profile_image', 'url'] as $field) {
                    if (! empty($profile->{$field})) {
                        $avatarRaw = $profile->{$field};
                        break;
                    }
                }
                if (! $avatarRaw && method_exists($profile, 'media')) {
                    $first = $profile->media()->first();
                    $avatarRaw = $first?->url ?? null;
                }
            }
            if (! $avatarRaw && $user && method_exists($user, 'media')) {
                $first = $user->media()->first();
                $avatarRaw = $first?->url ?? null;
            }

            $avatarFull = $avatarRaw ? (filter_var($avatarRaw, FILTER_VALIDATE_URL) ? $avatarRaw : Storage::disk('public')->url(ltrim(preg_replace('#^(storage/)+#', 'storage/', $avatarRaw), '/'))) : null;

            $userRatingValue = optional($comment->user->ratings()->where('place_id', $this->id)->first())->rating_value ?? 0;

            return [
                'id' => $comment->id,
                'user_id' => $comment->user_id,
                'user_name' => $userName,
                'user_avatar' => $avatarFull,
                'body' => $comment->body,
                'created_at' => $comment->created_at?->toDateString(),
                'rating_value' => $userRatingValue,
            ];
        })->values()->all();

        $cityName = null;
        try {
            if ($this->relationLoaded('city') && $this->city) {
                $cityName = $this->city->name ?? null;
            } else {
                $cityId = $this->resource->city_id ?? null;
                if ($cityId) {
                    $city = City::find($cityId);
                    $cityName = $city ? $city->name : null;
                }
            }
        } catch (\Throwable $e) {
            $cityName = null;
        }

        return [
            'id' => $this->resource->id,
            'city' => $cityName,
            'type' => $this->resource->type,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'number_of_branches' => $this->resource->number_of_branches,
            'phone' => $this->resource->phone,
            'country_code' => $this->resource->country_code,
            'place' => $this->resource->place,
            'longitude' => $this->resource->longitude,
            'latitude' => $this->resource->latitude,
            'rating' => round($avgRating, 2),
            'classification' => $this->resource->classification,
            'images' => $images,
            'rank' => $this->rank ?? null,
            'is_saved' => $isSaved,
            'user_rating' => $userRating,
            'user_comment' => $userComment,
            'recent_comments' => $recentComments,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
