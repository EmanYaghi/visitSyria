<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CommentResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $this->whenLoaded('user') ?? $this->user ?? null;

        // flexible body: support both 'body' and 'comment' DB column names
        $body = $this->body ?? $this->comment ?? null;

        // user display
        $userName = null;
        $profilePhoto = null;
        if ($user) {
            // try profile names, fallback to user->name or email
            $first = $user->profile->first_name ?? null;
            $last  = $user->profile->last_name ?? null;
            $userName = trim(($first . ' ' . $last) ?: ($user->name ?? $user->email ?? null));

            // get photo from profile->photo or user->media->url
            $photoPath = $user->profile->photo ?? ($user->media->url ?? null);
            $profilePhoto = $photoPath ? $this->buildFullUrl($photoPath) : null;
        }

        return [
            'id' => $this->id,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $userName,
                'profile_photo' => $profilePhoto,
            ] : null,
            // return comment text under 'comment' key (matches your expected output)
            'comment' => $body,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
        ];
    }

    protected function buildFullUrl(?string $path): ?string
    {
        if (!$path) return null;

        $path = trim($path);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        // already has /storage prefix
        if (Str::startsWith($path, '/storage')) {
            return url($path);
        }

        // otherwise assume it's a storage path (e.g. 'posts/xxx.jpg')
        return url('storage/' . ltrim($path, '/'));
    }
}
