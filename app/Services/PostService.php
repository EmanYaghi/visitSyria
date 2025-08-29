<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Media;
use App\Models\Notification;
use App\Models\TagName;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class PostService
{
    public function createPost($user, array $data): Post
    {
        return DB::transaction(function () use ($user, $data) {
            $post = Post::create([
                'user_id' => $user->id,
                'description' => $data['description'] ?? null,
            ]);

            // handle tags which may be passed as names (strings) or ids (integers)
            if (!empty($data['tags']) && is_array($data['tags'])) {
                $tagIds = [];

                foreach ($data['tags'] as $tag) {
                    // numeric -> treat as existing TagName id
                    if (is_numeric($tag)) {
                        $found = TagName::find((int) $tag);
                        if ($found) {
                            $tagIds[] = $found->id;
                        }
                        continue;
                    }

                    // string -> treat as name, create if not exists
                    $name = trim((string) $tag);
                    if ($name === '') continue;

                    $tagModel = TagName::firstOrCreate(
                        ['body' => $name, 'follow_to' => 'post']
                    );

                    $tagIds[] = $tagModel->id;
                }

                // remove duplicates and sync
                $tagIds = array_values(array_unique($tagIds));
                if (!empty($tagIds)) {
                    $post->tags()->sync($tagIds);
                }
            }

            // handle image upload
            if (!empty($data['image']) && $data['image'] instanceof UploadedFile) {
                $path = $data['image']->store('posts', 'public'); // storage/app/public/posts
                $url = Storage::url($path);

                Media::create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'url' => $url,
                ]);
            }
            \App\Models\Notification::create([
                'id'              => Str::uuid(),
                'type'            => 'App\Notifications\UserNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id'   => User::role('super_admin')->first()->id,
                'data'            => json_encode([
                    'title'   => 'منشور جديد',
                    'message' => 'لديك منشور جديد لتوافق عليه او ترفضه',
                ]),
            ]);
            return $post->load(['tags', 'media']);
        });
    }

    public function updateStatus(int $postId, string $status): Post
    {
        $post = Post::findOrFail($postId);
        $post->status = $status;
        $post->save();
        $notificationService=new NotificationService();
        if($status=='Approved')
            $notificationService->send($post->user,$status,'Your post has been published successfully');
        else if($status=='Rejected')
            $notificationService->send($post->user, $status,'Your post has been rejected for violating our terms of publication.');
        return $post;
    }

    public function getUserPosts(User $user, ?string $status = null, ?int $limit = null)
    {
        $allowedStatuses = ['Pending', 'Approved', 'Rejected'];

        $query = Post::query()
            ->where('user_id', $user->id)
            ->with([
                'user.profile',
                'user.media',
                'media',
                'tags',
                'comments.user.profile',
                'likes',
                'saves',
            ])
            ->withCount(['likes', 'comments', 'saves'])
            ->orderByDesc('created_at');

        if ($status !== null) {
            if (!in_array($status, $allowedStatuses, true)) {
                throw new \InvalidArgumentException('Invalid status. Allowed: ' . implode(', ', $allowedStatuses));
            }
            $query->where('status', $status);
        }

        if ($limit !== null && $limit > 0) {
            $query->limit((int) $limit);
        }

        return $query->get();
    }
    public function deletePost(Post $post): void
    {
        DB::transaction(function () use ($post) {
            foreach ($post->media as $media) {
                if ($media->url && Storage::disk('public')->exists(str_replace('/storage/', '', $media->url))) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $media->url));
                }
                $media->delete();
            }

            $post->comments()->delete();
            $post->likes()->delete();
            $post->saves()->delete();
            $post->tags()->detach();
            $post->delete();
        });
    }

}
