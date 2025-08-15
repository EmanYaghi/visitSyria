<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PostResource extends JsonResource
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

    protected function userDisplayName($user): ?string
    {
        if (!$user) return null;

        if (method_exists($user, 'profile') && $user->profile && ($user->profile->first_name || $user->profile->last_name)) {
            $first = trim((string) ($user->profile->first_name ?? ''));
            $last  = trim((string) ($user->profile->last_name ?? ''));
            $name = trim("$first $last");
            if (!empty($name)) return $name;
        }

        if (method_exists($user, 'adminProfile') && $user->adminProfile && ($user->adminProfile->name_of_company ?? null)) {
            return $user->adminProfile->name_of_company;
        }

        if (!empty($user->name)) return $user->name;

        return $user->email ?? null;
    }

    public function toArray($request)
    {
        // ensure relevant relations are loaded to avoid N+1
        $this->loadMissing([
            'user.profile',
            'user.media',
            'media',
            'tags',
            'comments.user.profile',
            'comments.user.media',
            'likes',
            'saves',
        ]);

        $user = $this->user;
        $authUser = $request->user();

        // profile photo preference
        $profilePhotoPath = null;
        if ($user) {
            if (isset($user->profile) && !empty($user->profile->photo)) {
                $profilePhotoPath = $user->profile->photo;
            } elseif (method_exists($user, 'media') && $user->media) {
                $profilePhotoPath = $user->media->url ?? null;
            }
        }
        $profilePhotoFull = $this->toFullUrl($profilePhotoPath);

        // post image: first media record
        $postMedia = $this->media->first() ?? null;
        $imageFull = $postMedia && !empty($postMedia->url) ? $this->toFullUrl($postMedia->url) : null;

        // tags: TagName.body values
        $tags = $this->tags->pluck('body')->filter()->values()->all();

        // is_liked / is_saved: return null if no auth user, otherwise boolean
        if ($authUser) {
            $isLiked = (bool) $this->likes->where('user_id', $authUser->id)->count();
            $isSaved = (bool) $this->saves->where('user_id', $authUser->id)->count();
        } else {
            $isLiked = null;
            $isSaved = null;
        }

        // comments: use CommentResource which already handles body/comment fallback
        $comments = \App\Http\Resources\CommentResource::collection($this->comments);

        // owner display name
        $userDisplayName = $this->userDisplayName($user);

        return [
            'id' => $this->id,
            'user' => [
                'id' => $user->id ?? null,
                'name' => $userDisplayName,
                'profile_photo' => $profilePhotoFull,
            ],
            'description' => $this->description,
            'image' => $imageFull,
            'tags' => $tags,
            'is_liked' => $isLiked,
            'is_saved' => $isSaved,
            'comments' => $comments,
            'created_at' => optional($this->created_at)->format('Y-m-d'),
        ];
    }
}
