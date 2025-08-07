<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShowPlaceResource extends JsonResource
{
    public function toArray($request)
    {
        $token  = $request->bearerToken();
        $userId = $request->user()?->id;

        $avgRating = $this->ratings()->avg('rating_value') ?: 0;
        $data = [
            'token'             => $token ?: null,
            'id'                  => $this->id,
            'city_id'             => $this->city_id,
            'type'                => $this->type,
            'name'                => $this->name,
            'description'         => $this->description,
            'number_of_branches'  => $this->number_of_branches,
            'phone'               => $this->phone,
            'country_code'        => $this->country_code,
            'place'               => $this->place,
            'longitude'           => $this->longitude,
            'latitude'            => $this->latitude,
            'rating'              => round($avgRating, 2),
            'classification' => $this->classification,
            'images' => $this->media->map(fn($media) => asset('storage/' . $media->url)),
                        'is_commented'      => $userId !== null
                ? (bool) $this->comments()->where('user_id', $userId)->exists()
                : 'guest',
            'is_rated'          => $userId !== null
                ? (bool) $this->ratings()->where('user_id', $userId)->exists()
                : 'guest',
            'is_saved'    => $userId !== null
            ? (bool) $this->saves()->where('user_id', $userId)->exists()
            : 'guest',

            'recent_comments' => $this->latestcomments->map(function ($comment) {
                $profile = $comment->user?->profile;
                $userRating = $comment->user->ratings()->where('place_id', $this->id)->first();
                return [
                    'id' => $comment->id,
                    'user_id' => $comment->user_id,
                    'user_name' => $profile ? $profile->first_name . ' ' . $profile->last_name : null,
                    'body' => $comment->body,
                    'created_at' => $comment->created_at->toDateString(),
                    'rating_value' => $userRating ? $userRating->rating_value : 0,
                ];
            }),

            'created_at'          => $this->created_at->toDateTimeString(),
            'updated_at'          => $this->updated_at->toDateTimeString(),
        ];

        return $data;
    }
}
