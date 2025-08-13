<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArticleResource extends JsonResource
{
    public function toArray($request)
    {
        $this->loadMissing('media');

        $raw = $this->media?->url ?? null;

        if (! $raw) {
            $full = null;
        } elseif (filter_var($raw, FILTER_VALIDATE_URL)) {
            $full = $raw;
        } else {
            $full = Storage::disk('public')->url(ltrim($raw, '/'));
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
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
