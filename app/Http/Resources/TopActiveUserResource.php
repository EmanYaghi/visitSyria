<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class TopActiveUserResource extends JsonResource
{
    protected function toFullUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $path = trim($path);

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (Str::startsWith($path, ['/storage', 'storage'])) {
            $trimmed = '/' . ltrim($path, '/');
            return url($trimmed);
        }

        try {
            $storageUrl = Storage::url($path);
            if (filter_var($storageUrl, FILTER_VALIDATE_URL)) {
                return $storageUrl;
            }
            return url($storageUrl);
        } catch (\Throwable $e) {
            return url('/storage/' . ltrim($path, '/'));
        }
    }

    protected function displayName($user): ?string
    {
        if (!$user) return null;

        if (isset($user->profile) && ($user->profile->first_name || $user->profile->last_name)) {
            $first = trim((string) ($user->profile->first_name ?? ''));
            $last  = trim((string) ($user->profile->last_name ?? ''));
            $name = trim("$first $last");
            if (!empty($name)) return $name;
        }

        if (!empty($user->name)) return $user->name;

        return $user->email ?? null;
    }

    public function toArray($request)
    {
        $this->loadMissing(['profile', 'media']);

        $user = $this;
        $profilePhotoPath = null;
        if ($user) {
            if (isset($user->profile) && !empty($user->profile->photo)) {
                $profilePhotoPath = $user->profile->photo;
            } elseif (isset($user->media) && $user->media) {
                $firstMedia = $user->media->first();
                $profilePhotoPath = $firstMedia->url ?? null;
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->displayName($user),
            'profile_photo' => $this->toFullUrl($profilePhotoPath),
            'posts_count' => (int) ($this->posts_count ?? 0),
        ];
    }
}
