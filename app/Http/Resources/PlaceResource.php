<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray($request)
    {
        $avgRating = $this->ratings()->avg('rating_value') ?: 0;
        $userId = auth('api')->check() ? auth('api')->id() : null;

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
            'rating'              => round($avgRating, 2),
            'classification' => $this->resource->classification,
            'images' => $this->media->map(fn($media) => asset('storage/' . $media->url)),
            'rank' => $this->rank ?? null,
            'is_saved' => $userId
                ? (bool) $this->saves()->where('user_id', $userId)->exists()
                : 'guest',
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
