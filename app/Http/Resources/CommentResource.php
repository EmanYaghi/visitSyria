<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CommentResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $this->whenLoaded('user') ?? $this->user ?? null;

        $body = $this->body ?? $this->comment ?? null;

        $userName = null;
        $profilePhoto = null;
        if ($user) {
            $first = $user->profile->first_name ?? null;
            $last  = $user->profile->last_name ?? null;
            $userName = trim(($first . ' ' . $last) ?: ($user->name ?? $user->email ?? null));

            $photoPath = $user->profile->photo ?? optional($user->media->first())->url ?? null;
            $profilePhoto = $photoPath ? $this->buildFullUrl($photoPath) : null;
        }

        return [
            'id' => $this->id,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $userName,
                'profile_photo' => $profilePhoto,
            ] : null,
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

        if (Str::startsWith($path, '/storage')) {
            return url($path);
        }

        return url('storage/' . ltrim($path, '/'));
    }
}
