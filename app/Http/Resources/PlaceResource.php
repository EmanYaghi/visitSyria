<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray($request)
    {
        $avgRating = $this->ratings()->avg('rating_value') ?: 0;
        $userId = auth('api')->check() ? auth('api')->id() : null;
                if ($userId) {
            $userRatingModel = $this->ratings()->where('user_id', $userId)->first();
            $userRating = $userRatingModel?->rating_value ?? null;

            $userCommentModel = $this->comments()->where('user_id', $userId)->first();
            $userComment = $userCommentModel?->body ?? null;
        } else {
            $userRating = 'guest';
            $userComment = 'guest';
        }

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
            'images' => $this->media->map(fn($media) => asset('storage/' . $media->url)),
            'rank' => $this->rank ?? null,

            'user_rating' => $userRating,
            'user_comment' => $userComment,

            'recent_comments' => $this->latestComments->map(function ($comment) {
                $profile = $comment->user?->profile;
                $userRating = $comment->user->ratings()
                    ->where('place_id', $this->id)
                    ->first();

                return [
                    'id' => $comment->id,
                    'user_id' => $comment->user_id,
                    'user_name' => $profile
                        ? $profile->first_name . ' ' . $profile->last_name
                        : null,
                    'body' => $comment->body,
                    'created_at' => $comment->created_at->toDateString(),
                    'rating_value' => $userRating?->rating_value ?? 0,
                ];
            }),

            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
