<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Http\Resources\PostResource;
use App\Http\Requests\StorePostRequest;
use App\Services\PostService;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    protected PostService $postService;

    public function __construct(PostService $postService)
    {
        // حسب طلبك: لا نستخدم $this->middleware(...) داخل الكونستركتور
        $this->postService = $postService;
    }

    /**
     * GET /posts
     *
     * Query params:
     *  - tag: string (optional)
     *  - status: Pending|Approved|Rejected (optional - only accepted for admin|super_admin)
     *
     * Behavior:
     *  - If no status param => only Approved posts are returned (public feed).
     *  - If status param is provided:
     *      - if user is authenticated and has role admin|super_admin => filter by that status.
     *      - otherwise ignore status and return only Approved.
     */
    public function index(Request $request)
    {
        $tag = $request->query('tag');
        $status = $request->query('status'); // optional

        $allowedStatuses = ['Pending', 'Approved', 'Rejected'];

        // default behavior for public: only Approved
        $useStatus = 'Approved';

        $authUser = $request->user();
        if ($status && in_array($status, $allowedStatuses, true) && $authUser && $authUser->hasAnyRole(['admin', 'super_admin'])) {
            // authenticated admin/super_admin can request other statuses
            $useStatus = $status;
        }

        $query = Post::query()
            ->with([
                'user.profile',
                'user.media',
                'media',
                'tags',                   // TagName models via belongsToMany
                'comments.user.profile',
                'likes',
                'saves',
            ])
            ->where('status', $useStatus)
            ->orderByDesc('created_at');

        if ($tag) {
            // filter by tag body (tag name)
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('body', $tag);
            });
        }

        $posts = $query->get();

        return PostResource::collection($posts);
    }

public function byStatus(Request $request)
{
    $tag = $request->query('tagName') ?? $request->query('tag'); // قبول tagName أو tag
    $status = $request->query('status'); // Pending|Approved|Rejected

    // تأكد من القيم المسموح بها للحالة (اختياري لكن مفيد)
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
            'tags',                  // TagName models via belongsToMany
            'comments.user.profile', // comments with user profile
            'likes',
            'saves',
        ])
        ->orderByDesc('created_at');

    if ($status) {
        $query->where('status', $status);
    }

    // فلترة حسب التاغ إن مرّ (نستخدم whereHas على علاقة tags)
    if ($tag) {
        $query->whereHas('tags', function ($q) use ($tag) {
            $q->where('body', $tag);
        });
    }

    $posts = $query->get();

    return PostResource::collection($posts);
}
    public function store(StorePostRequest $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        // PostService should handle tags as names (body) -> map/create TagName and attach
        $post = $this->postService->createPost($user, $data);

        $post->load(['user.profile', 'user.media', 'media', 'tags', 'comments.user.profile', 'likes', 'saves']);

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    /**
     * POST /posts/status
     * body: { post_id: int, status: "Pending|Approved|Rejected" }
     * Only super_admin allowed.
     */
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

        return new PostResource($post);
    }
}
