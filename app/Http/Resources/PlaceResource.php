<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray($request)
    {
        $avg = $this->ratings->avg('rating');
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
            'rating' => $avg ? round($avg, 2) : 0,
            'classification' => $this->resource->classification,
            'images' => $this->media->map(fn($media) => asset('storage/' . $media->url)),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
