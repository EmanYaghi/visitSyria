<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\Save;

class ArticleResource extends JsonResource
{
    public function toArray($request)
    {
        $this->loadMissing(['media', 'tags.tagName']);

        $raw = $this->media?->url ?? null;

        if (! $raw) {
            $full = null;
        } elseif (filter_var($raw, FILTER_VALIDATE_URL)) {
            $full = $raw;
        } else {
            $full = Storage::disk('public')->url(ltrim($raw, '/'));
        }

        $user = $request->user('api');

        if (! $user) {
            $isSaved = null;
        } else {
            if ($this->relationLoaded('saves')) {
                $isSaved = $this->saves->contains('user_id', $user->id);
            } else {
                $isSaved = Save::where('article_id', $this->id)
                                ->where('user_id', $user->id)
                                ->exists();
            }
            $isSaved = (bool) $isSaved;
        }

        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'body'       => $this->body,
            'image_url'  => $full,
            'tags'       => $this->tags
                                ->map(fn($t) => $t->tagName?->body)
                                ->filter()
                                ->values()
                                ->all(),
            'is_saved'   => $isSaved,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
