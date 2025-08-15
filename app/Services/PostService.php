<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostService
{
    public function createPost($user, array $data): Post
    {
        $post = Post::create([
            'user_id' => $user->id,
            'description' => $data['description'] ?? null,
            // status default 'Pending' per migration/model
        ]);

        // attach tags if provided (tags are tag_name ids)
        if (!empty($data['tags']) && is_array($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        if (!empty($data['image']) && $data['image'] instanceof UploadedFile) {
            $path = $data['image']->store('posts', 'public'); // storage/app/public/posts
            $url = Storage::url($path);
            Media::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'url' => $url,
            ]);
        }

        return $post->load(['tags', 'media']);
    }

    public function updateStatus(int $postId, string $status): Post
    {
        $post = Post::findOrFail($postId);
        $post->status = $status;
        $post->save();
        return $post;
    }
}
