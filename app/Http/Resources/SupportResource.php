<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $this->whenLoaded('user') ? $this->user : $this->user()->with(['profile','adminProfile','media'])->first();

        $name = null;
        if ($user) {
            if (!empty($user->profile) && ($user->profile->first_name || $user->profile->last_name)) {
                $name = trim(($user->profile->first_name ?? '') . ' ' . ($user->profile->last_name ?? ''));
            } elseif (!empty($user->adminProfile) && !empty($user->adminProfile->name_of_company)) {
                $name = $user->adminProfile->name_of_company;
            } else {
                $name = $user->name ?? $user->email ?? null;
            }
        }

        $photoPath = null;
        if ($user) {
            if (!empty($user->profile) && !empty($user->profile->photo)) {
                $photoPath = $user->profile->photo;
            } elseif (!empty($user->adminProfile) && !empty($user->adminProfile->image)) {
                $photoPath = $user->adminProfile->image;
            } elseif (!empty($user->media) && !empty($user->media->url)) {
                $photoPath = $user->media->url;
            }
        }

        $photoUrl = $this->makeAbsoluteUrl($photoPath);

        return [
            'id' => $this->id,
            'user' => [
                'id' => $user->id ?? null,
                'name' => $name,
                'profile_photo' => $photoUrl,
            ],
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
        ];
    }

    protected function makeAbsoluteUrl(?string $path): ?string
    {
        if (empty($path)) return null;
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return url($path);
    }
}
