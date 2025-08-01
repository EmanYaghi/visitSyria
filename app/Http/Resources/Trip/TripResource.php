<?php

namespace App\Http\Resources\Trip;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $companyId=$this->user->id;
        $user=Auth::user();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'season' => $this->season,
            'start_date' => $this->start_date,
            'duration' => $this->duration,
            'remaining_tickets'=>($this->tickets)-($this->reserved_tickets),
            'price' => $this->price,
            'discount' => $this->discount,
            'new_price' => $this->new_price,
            'improvements' => json_decode($this->improvements, true),
            'status'=>$this->status,
            'tags' => $this->tags->map(function ($tag) {
                    return optional($tag->tagName)->body;
                })->filter()->values()->all(),

            'images' => $this->media->map(function ($media) {
                return asset('storage/' . $media->url);
            }),

            'timelines' => $this->timelines->map(function ($timeline) {
                return [
                    'day_number' => $timeline->day_number,
                    'sections' => $timeline->sections->map(function ($section) {
                        return [
                            'time' => $section->time,
                            'title' => $section->title,
                            'description' => $section->description,
                            'longitude'=>$section->longitude,
                            'latitude'=>$section->latitude
                        ];
                    }),
                ];
            }),
            'company' => [
                'id'=>$companyId,
                'name' => $this->user->adminProfile->name_of_company,
                'image' => $this->user->adminProfile->image,
                'rating'=> Rating::whereHas('trip', function($query) use ($companyId) {
                        $query->where('user_id', $companyId);
                    })
                    ->whereNotNull('trip_id')
                    ->avg('rating_value')
            ],
             'is_saved' => $user
                ? $this->saves()->where('user_id', $user->id)->exists()
                : false,
        ];
    }
}
