<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PlaceResource extends JsonResource
{
    protected function normalizeMediaUrl(?string $raw): ?string
    {
        if (! $raw) return null;

        // إذا هو رابط كامل
        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            return $raw;
        }

        // إذا بدأ بـ /storage/ أو storage/ — نحدف الـ prefix ونبني رابط public
        $path = $raw;
        if (str_starts_with($path, '/storage/')) {
            $path = ltrim(substr($path, strlen('/storage/')), '/');
        } elseif (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        // الآن path متوقع مثل 'places/xxx.jpg'
        return Storage::disk('public')->url(ltrim($path, '/'));
    }

    public function toArray($request)
    {
        // نحمّل العلاقات الضرورية إذا لم تكن محمّلة
        $this->loadMissing([
            'media',
            'ratings',
            'comments.user.profile',
            'comments.user.media',
            'latestComments.user.profile',
            'latestComments.user.media',
        ]);

        $avgRating = (float) ($this->ratings()->avg('rating_value') ?: 0);

        $user = $request->user('api');
        $userId = $user ? $user->id : null;

        $userRating = $userId ? $this->ratings()->where('user_id', $userId)->value('rating_value') ?? null : null;
        $userComment = $userId ? $this->comments()->where('user_id', $userId)->value('body') ?? null : null;

        // images: نستخدم العلاقة المحمّلة إن وُجدت، أو نجلبها إن لم تكن محمّلة
        $mediaCollection = $this->relationLoaded('media') ? $this->media : $this->media()->get();

        $images = $mediaCollection->map(fn($m) => $this->normalizeMediaUrl($m->url))
                                 ->filter()
                                 ->values()
                                 ->all();

        // is_saved: نعطي أولوية لخاصية set من الـ Service (is_saved) إذا وُجِدت
        if (array_key_exists('is_saved', $this->resource->getAttributes())) {
            $isSaved = $this->resource->is_saved;
        } else {
            if ($userId === null) {
                $isSaved = null;
            } else {
                $s = $this->saves()->where('user_id', $userId)->whereNotNull('place_id')->exists();
                $isSaved = (bool) $s;
            }
        }

        // آخر 3 تعليقات
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

            $userName = $profile
                ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''))
                : ($user?->name ?? null);

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

            $avatarFull = $avatarRaw
                ? (filter_var($avatarRaw, FILTER_VALIDATE_URL) ? $avatarRaw : Storage::disk('public')->url(ltrim($avatarRaw, '/')))
                : null;

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

        return [
            'id' => $this->resource->id,
            'city_id' => $this->resource->city_id,
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
