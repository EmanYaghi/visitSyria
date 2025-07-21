<?php

namespace App\Http\Resources\Trip;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class TripResource extends JsonResource
{
    protected function getFeedback()
    {
        $comments = $this->comments()->latest()->get();
        $feedbackList = [];
        foreach ($comments as $comment) {
            $rating = $this->ratings()
                ->where('user_id', $comment->user_id)
                ->latest()
                ->first();
            $feedbackList[] = [
                'id'=>$comment->user->id,
                'user_name' => $comment->user->profile->first_name ." ".$comment->user->profile->last_name ,
                'photo'=>$comment->user->profile->photo,
                'comment_body' => $comment->body,
                'rating_value' => $rating ? $rating->rating_value : null,
                'created_at' => $comment->created_at->diffForHumans(),
            ];
        }
        return $feedbackList;
    }


    public function toArray(Request $request): array
    {
        $user=Auth::user();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'season' => $this->season,
            'start_date' => $this->start_date,
            'duration' => $this->duration,
            'tickets' => $this->tickets,
            'remaining_tickets'=>($this->tickets)-($this->reserved_tickets),
            'price' => $this->price,
            'discount' => $this->discount,
            'new_price' => $this->new_price,
            'improvements' => json_decode($this->improvements, true),
            'status'=>$this->status,
            'tags' => $this->tags->map(function ($tag) {
                return [
                    'body' => optional($tag->tagName)->body,
                ];
            }),

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
                        ];
                    }),
                ];
            }),
            'company' => [
                'id'=>$this->user->id,
                'name' => $this->user->adminProfile->name_of_company,
                'image' => $this->user->adminProfile->image,
                'rating'=>$this->user->adminProfile->rating
            ],

             'is_saved' => $user
                ? $this->saves()->where('user_id', $user->id)->exists()
                : false,

            'feedback' => $this->getFeedback(),
        ];




    }
}
