<?php

namespace App\Http\Resources\Trip;

use App\Http\Resources\CommentResource;
use App\Http\Resources\FeedbackResource;
use App\Models\Rating;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Traits\TripPath;
use App\Services\RouteService;

class TripWithFeedbackResource extends JsonResource
{
    use TripPath;

    public function toArray(Request $request): array
    {
        $user = Auth::user();
        $trip = Trip::with(['user.adminProfile', 'tags.tagName', 'media', 'timelines.sections'])
                    ->find($this->id);

        $company = optional($trip->user);
        $adminProfile = optional($company->adminProfile);
        $status = ($user && $this->bookings
            ->where('user_id', $user->id)
            ->where('is_paid', false)
            ->isNotEmpty())
            ? 'غير مكتملة'
            : $this->status;
        $rs = app(RouteService::class);

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'description'       => $this->description,
            'season'            => $this->season,
            'start_date'        => $this->start_date,
            'days'              =>$this->days." days",
            'tickets'           =>$this->tickets,
            'remaining_tickets' => ($this->tickets ?? 0) - ($this->reserved_tickets ?? 0),
            'price'             => $this->price,
            'discount'          => $this->discount,
            'new_price'         => $this->new_price,
            'improvements'      => json_decode($this->improvements ?? '[]', true),
            'status'            => $status,

            'tags' => $this->tags->map(function ($tag) {
                return optional($tag->tagName)->body;
            })->filter()->values()->all(),

            'images' => $this->media->map(function ($media) {
                return asset('storage/' . $media->url);
            }),

            "trip_path" => $this->getRoute($trip)?:null,

            'timelines' => $this->timelines->map(function ($timeline) {
                return [
                    'day_number' => $timeline->day_number,
                    'sections'   => $timeline->sections->map(function ($section) {
                        return [
                            'time' => substr($section->time, 0, 5),
                            'title'       => $section->title,
                            'description' => $section->description,
                        ];
                    }),
                ];
            }),

            'company' => [
                'id'     => $company->id,
                'name'   => $adminProfile->name_of_company,
                'image'  => $adminProfile->image,
                'rating' => $company->id
                    ? Rating::whereHas('trip', function ($query) use ($company) {
                        $query->where('user_id', $company->id);
                    })
                    ->whereNotNull('trip_id')
                    ->avg('rating_value')
                    : null,
            ],
            'feedback' => $this->comments
                ? FeedbackResource::collection($this->comments)
                : [],
        ];
    }
}
