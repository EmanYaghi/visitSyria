<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShowPlaceResource extends JsonResource
{
    public function toArray($request)
    {
        $avg = $this->ratings->avg('rating_value') ?: 0;
        $recent = $this->comments->map(function ($comment) {
            return [
                'id'           => $comment->id,
                'user_id'      => $comment->user_id,
                'user_name'    => $comment->user->name,
                'body'         => $comment->body,
                'created_at'   => $comment->created_at->toDateTimeString(),
                'rating_value' => optional(
                    $this->ratings->firstWhere('user_id', $comment->user_id)
                )->rating_value ?: 0,
            ];
        })->values();

        return [
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
            'rating'              => round($avg, 2),
            'classification'      => $this->classification,
            'images'              => $this->media->map(fn($m) => asset('storage/'.$m->url)),
            'recent_comments'     => $recent,
            'rank'                => $this->rank,
            'created_at'          => $this->created_at->toDateTimeString(),
            'updated_at'          => $this->updated_at->toDateTimeString(),
        ];
    }
}
