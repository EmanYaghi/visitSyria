<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
         return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->profile->first_name.' '.$this->user->profile->last_name,
                'profile_photo' => $this->user->profile->image?asset('storage/' . $media->url): null,
            ],
            'comment' => $this->body,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
        ];
    }
}
