<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\TopActiveUserResource;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Throwable;

class PostController extends Controller
{
    protected PostService $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function index(Request $request)
    {
        $tag = $request->query('tag');
        $status = $request->query('status');

        $allowedStatuses = ['Pending', 'Approved', 'Rejected'];

        $useStatus = 'Approved';

        $authUser = $request->user();
        if ($status && in_array($status, $allowedStatuses, true) && $authUser && $authUser->hasAnyRole(['admin', 'super_admin'])) {
            $useStatus = $status;
        }

        $query = Post::query()
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
            ->where('status', $useStatus)
            ->orderByDesc('created_at');

        if ($tag) {
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('body', $tag);
            });
        }

        $posts = $query->get();

        return PostResource::collection($posts);
    }

    public function byStatus(Request $request)
    {
        $tag = $request->query('tagName') ?? $request->query('tag');
        $status = $request->query('status');

        $allowed = ['Pending', 'Approved', 'Rejected'];
        if ($status && !in_array($status, $allowed, true)) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid status. Allowed: ' . implode(', ', $allowed)
            ], 422);
        }

        $query = Post::query()
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

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'Approved');
        }

        if ($tag) {
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('body', $tag);
            });
        }

        $posts = $query->get();

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $post = $this->postService->createPost($user, $data);

        $post->load(['user.profile', 'user.media', 'media', 'tags', 'comments.user.profile', 'likes', 'saves']);
        $post->loadCount(['likes', 'comments', 'saves']);

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    public function show(Post $post, Request $request)
    {
        $authUser = $request->user();

        if (! $authUser || ! $authUser->hasAnyRole(['admin', 'super_admin'])) {
            if ($post->status !== 'Approved') {
                return response()->json(['message' => 'Not Found'], 404);
            }
        }

        $post->load([
            'user.profile',
            'user.media',
            'media',
            'tags',
            'comments.user.profile',
            'likes',
            'saves',
        ]);
        $post->loadCount(['likes', 'comments', 'saves']);

        return new PostResource($post);
    }

    public function updateStatus(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! $user->hasRole('super_admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'post_id' => 'required|integer|exists:posts,id',
            'status'  => 'required|string|in:Pending,Approved,Rejected',
        ]);

        $post = $this->postService->updateStatus((int)$data['post_id'], $data['status']);

        $post->load(['user.profile', 'user.media', 'media', 'tags', 'comments.user.profile', 'likes', 'saves']);
        $post->loadCount(['likes', 'comments', 'saves']);

        return new PostResource($post);
    }

public function topActiveUsers(Request $request)
{
    $limit = (int) $request->query('limit', 10);
    $limit = $limit > 0 && $limit <= 100 ? $limit : 10;

    $users = User::role('client')
        ->whereHas('posts', function ($q) {
            $q->where('status', 'Approved');
        })
        ->with(['profile', 'media'])
        ->withCount(['posts' => function ($q) {
            $q->where('status', 'Approved');
        }])
        ->orderByDesc('posts_count')
        ->limit($limit)
        ->get();

    return TopActiveUserResource::collection($users);
}
    public function myPosts(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $status = $request->query('status');
        $limit = $request->query('limit') ? (int) $request->query('limit') : null;

        try {
            $posts = $this->postService->getUserPosts($user, $status, $limit);
        } catch (Throwable $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);
        }

        return PostResource::collection($posts);
    }

}
