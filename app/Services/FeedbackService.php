<?php

namespace App\Services;

use App\Http\Resources\ArticleResource;
use App\Http\Resources\Auth\AdminProfileResource;
use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\PlaceResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\Trip\TripResource;
use App\Models\AdminProfile;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Place;
use App\Models\Post;
use App\Models\Rating;
use App\Models\Save;
use App\Models\Trip;
use App\Models\Like;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class FeedbackService
{
    public function setSave($id)
    {
        $type = request()->query('type');
        $user = Auth::user();
        $code = 201;
        $message = 'added to saves';
        if (Trip::find($id) && $type == "trip" && ! Save::where('user_id', $user->id)->where('trip_id', $id)->first()) {
            $user->saves()->create(["trip_id" => $id]);
        } else if (Post::find($id) && $type == "post" && ! Save::where('user_id', $user->id)->where('post_id', $id)->first()) {
            $user->saves()->create(["post_id" => $id]);
        } else if (Place::find($id) && $type == "place" && ! Save::where('user_id', $user->id)->where('place_id', $id)->first()) {
            $user->saves()->create(["place_id" => $id]);
        } else if (Article::find($id) && $type == "article" && ! Save::where('user_id', $user->id)->where('article_id', $id)->first()) {
            $user->saves()->create(["article_id" => $id]);
        } else if (Event::find($id) && $type == "event" && ! Save::where('user_id', $user->id)->where('event_id', $id)->first()) {
            $user->saves()->create(["event_id" => $id]);
        } else {
            $code = 404;
            $message = 'not found or pre saved';
        }
        return ['message' => $message, 'code' => $code];
    }
public function setRatingAndComment(array $payload, $id)
{
    $user = Auth::user();
    if (! $user) {
        return ['message' => 'Unauthenticated', 'code' => 401];
    }

    $type = request()->query('type');
    $allowedCommentTypes = [
        'trip'  => 'trip_id',
        'place' => 'place_id',
        'post'  => 'post_id',
    ];
    $allowedRatingTypes = [
        'trip'  => 'trip_id',
        'place' => 'place_id',
    ];

    if (! isset($allowedCommentTypes[$type])) {
        return ['message' => 'type not supported', 'code' => 400];
    }

    $colComment = $allowedCommentTypes[$type];
    $colRating = $allowedRatingTypes[$type] ?? null;

    $exists = match ($type) {
        'trip' => Trip::find($id),
        'place' => Place::find($id),
        'post' => Post::find($id),
        default => null,
    };

    if (! $exists) {
        return ['message' => 'target not found', 'code' => 404];
    }

    $ratingValue = array_key_exists('rating_value', $payload) ? $payload['rating_value'] : null;
    $body = array_key_exists('body', $payload) ? trim((string)$payload['body']) : null;

    $existingRating = $colRating ? Rating::where('user_id', $user->id)->where($colRating, $id)->first() : null;
    $existingComment = Comment::where('user_id', $user->id)->where($colComment, $id)->first();

    if ($body !== null && $body !== '' && $ratingValue === null && ! $existingRating) {
        return ['message' => 'Cannot add comment without a rating (either create rating first or include rating_value in this request).', 'code' => 422];
    }

    DB::beginTransaction();
    try {
        $finalRating = null;
        $finalComment = null;

        if ($ratingValue !== null) {
            if ($colRating === null) {
                DB::rollBack();
                return ['message' => 'rating not supported for this type', 'code' => 400];
            }
            $classification = ((int)$ratingValue >= 3) ? 'positive' : 'negative';
            if ($existingRating) {
                $existingRating->rating_value = (int)$ratingValue;
                $existingRating->classification = $classification;
                $existingRating->save();
                $finalRating = $existingRating->fresh();
            } else {
                $finalRating = $user->ratings()->create([
                    $colRating => $id,
                    'rating_value' => (int)$ratingValue,
                    'classification' => $classification,
                ]);
            }
        } else {
            $finalRating = $existingRating ? $existingRating->fresh() : null;
        }

        if ($body !== null && $body !== '') {
            if ($existingComment) {
                $existingComment->body = $body;
                $existingComment->save();
                $finalComment = $existingComment->fresh();
            } else {
                $finalComment = $user->comments()->create([
                    $colComment => $id,
                    'body' => $body,
                ]);
            }
        } else {
            $finalComment = $existingComment ? $existingComment->fresh() : null;
        }

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        return ['message' => $e->getMessage(), 'code' => 500];
    }

    $formatRating = null;
    if ($finalRating) {
        $formatRating = [
            'id' => $finalRating->id,
            'user_id' => $finalRating->user_id,
            $colRating => $finalRating->{$colRating},
            'rating_value' => (int) $finalRating->rating_value,
            'classification' => $finalRating->classification,
            'created_at' => $finalRating->created_at ? $finalRating->created_at->toDateString() : null,
            'updated_at' => $finalRating->updated_at ? $finalRating->updated_at->toDateString() : null,
        ];
    }

    $formatComment = null;
    if ($finalComment) {
        $formatComment = [
            'id' => $finalComment->id,
            'user_id' => $finalComment->user_id,
            $colComment => $finalComment->{$colComment},
            'comment' => $finalComment->body ?? $finalComment->comment ?? null,
            'created_at' => $finalComment->created_at ? $finalComment->created_at->toDateString() : null,
            'updated_at' => $finalComment->updated_at ? $finalComment->updated_at->toDateString() : null,
        ];
    }

    return [
        'comment' => $formatComment,
        'rating' => $formatRating,
        'message' => 'feedback set successfully',
        'code' => 201
    ];
}

    public function deleteSave($id)
    {
        $type = request()->query('type');
        $user = Auth::user();
        $code = 200;
        $message = 'deleted from saves';
        $save = null;
        if (Trip::find($id) && $type == "trip") {
            $save = Save::where('user_id', $user->id)->where('trip_id', $id)->first();
        } else if (Post::find($id) && $type == "post") {
            $save = Save::where('user_id', $user->id)->where('post_id', $id)->first();
        } else if (Place::find($id) && $type == "place") {
            $save = Save::where('user_id', $user->id)->where('place_id', $id)->first();
        } else if (Article::find($id) && $type == "article") {
            $save = Save::where('user_id', $user->id)->where('article_id', $id)->first();
        } else if (Event::find($id) && $type == "event") {
            $save = Save::where('user_id', $user->id)->where('event_id', $id)->first();
        }
        if ($save) {
            $save->delete();
        } else {
            $code = 404;
            $message = 'not found';
        }
        return ['message' => $message, 'code' => $code];
    }

    public function getSaves()
    {
        $type = request()->query('type');
        $user = Auth::user();
        $saves = null;
        if ($type == "trip") {
            $trips = $user->saves()->whereNotNull('trip_id')->with('trip')->get()->pluck('trip');
            $saves = TripResource::collection($trips);
        } else if ($type == "post") {
            $posts = $user->saves()->whereNotNull('post_id')->with('post')->get()->pluck('post');
            $saves =PostResource::collection($posts);
        } else if ($type == "restaurant") {
            $places = $user->saves()->whereNotNull('place_id')->with('place')->get()->pluck('place');
            $places = $places->filter(fn($place) => $place->type === 'restaurant');
            $saves = PlaceResource::collection($places);
        } else if ($type == "hotel") {
            $places = $user->saves()->whereNotNull('place_id')->with('place')->get()->pluck('place');
            $places = $places->filter(fn($place) => $place->type === 'hotel');
            $saves = PlaceResource::collection($places);
        } else if ($type == "tourist") {
            $places = $user->saves()->whereNotNull('place_id')->with('place')->get()->pluck('place');
            $places = $places->filter(fn($place) => $place->type === 'tourist');
            $saves = PlaceResource::collection($places);
        } else if ($type == "article") {
            $articles = $user->saves()->whereNotNull('article_id')->with('article')->get()->pluck('article');
            $saves = ArticleResource::collection($articles);
        } else if ($type == "event") {
            $events = $user->saves()->whereNotNull('event_id')->with('event')->get()->pluck('event');
            $saves = EventResource::collection($events);
        }
        return ['saves' => $saves];
    }

    public function setRating($request, $id)
    {
        $user = Auth::user();
        $type = request()->query('type');
        $ratingValue = $request['rating_value'];
        if ($ratingValue >= 3) {
            $classification = "positive";
        } else {
            $classification = "negative";
        }
        $message = "set rating successfully";
        $code = 201;
        $allowedTypes = [
            'trip'  => 'trip_id',
            'place' => 'place_id',
        ];
        if (!isset($allowedTypes[$type])) {
            return [
                "message" => "the type is not correct",
                "code"    => 400
            ];
        }
        $column = $allowedTypes[$type];
        $rate = Rating::where('user_id', $user->id)->where($column, $id)->first();
        if ($rate) {
            $rate->update(['rating_value' => $ratingValue, 'classification' => $classification]);
            $rate->save();
        } else {
            $user->ratings()->create([$column => $id, 'rating_value' => $ratingValue, 'classification' => $classification]);
        }
        return ["message" => $message, "code" => $code];
    }

    public function deleteRating($id)
    {
        $user = Auth::user();
        $type = request()->query('type');
        $message = "delet rating successfully";
        $code = 200;
        $user = Auth::user();
        $type = request()->query('type');
        $allowedTypes = [
            'trip'  => 'trip_id',
            'place' => 'place_id',
        ];
        if (!isset($allowedTypes[$type])) {
            return [
                "message" => "the type is not correct",
                "code"    => 400
            ];
        }
        $column = $allowedTypes[$type];
        $rate = Rating::where('user_id', $user->id)->where($column, $id)->first();
        if ($rate) {
            $rate->delete();
        }
        return ["message" => $message, "code" => $code];
    }

    public function setComment($request, $id)
    {
        $user = Auth::user();
        $type = request()->query('type');
        $message = "set comment successfully";
        $code = 201;
        $allowedTypes = [
            'trip'  => 'trip_id',
            'place' => 'place_id',
            'post'  => 'post_id'
        ];
        if (!isset($allowedTypes[$type])) {
            return [
                "message" => "the type is not correct",
                "code"    => 400
            ];
        }
        $column = $allowedTypes[$type];
        $comment = $user->comments()->create([$column => $id, 'body' => $request['body']]);
        return ["comment" => $comment, "message" => $message, "code" => $code];
    }

    public function deleteComment($id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            $message = "comment not found";
            $code = 404;
        } else {
            $comment->delete();
            $message = "delete comment successfully";
            $code = 200;
        }
        return ["message" => $message, "code" => $code];
    }

    public function getFeedback($id)
    {
        $type = request()->query('type');
        $ratingFilter = strtolower((string) request()->query('rating', 'all'));
        $allowedFilterValues = ['all', 'positive', 'negative'];
        if (!in_array($ratingFilter, $allowedFilterValues, true)) {
            $ratingFilter = 'all';
        }
        $allowedTypes = [
            'trip'    => 'trip_id',
            'place'   => 'place_id',
            'post'    => 'post_id',
            'event'   => 'event_id',
            'article' => 'article_id',
        ];
        if (!isset($allowedTypes[$type])) {
            return ['message' => 'type not supported', 'code' => 400];
        }
        $column = $allowedTypes[$type];
        $comments = Comment::where($column, $id)
            ->with(['user.profile', 'user.media'])
            ->orderByDesc('created_at')
            ->get();
        $userIds = $comments->pluck('user_id')->filter()->unique()->values()->all();
        $ratingsByUser = collect([]);
        if (in_array($type, ['trip', 'place'], true) && !empty($userIds)) {
            $ratingsByUser = Rating::where($column, $id)
                ->whereIn('user_id', $userIds)
                ->get()
                ->keyBy('user_id');
        }
        $filtered = $comments->filter(function ($c) use ($ratingsByUser, $ratingFilter) {
            if ($ratingFilter === 'all') return true;
            $r = $ratingsByUser->get($c->user_id);
            return $r && $r->classification === $ratingFilter;
        })->values();
        $result = $filtered->map(function ($c) use ($ratingsByUser) {
            $user = $c->user;
            $name = null;
            $photo = null;
            if ($user) {
                if (isset($user->profile) && ($user->profile->first_name || $user->profile->last_name)) {
                    $first = trim((string)($user->profile->first_name ?? ''));
                    $last = trim((string)($user->profile->last_name ?? ''));
                    $name = trim("$first $last");
                }
                if (empty($name) && !empty($user->name)) {
                    $name = $user->name;
                }
                if (empty($name)) {
                    $name = $user->email ?? null;
                }
                $photo = $user->profile->photo ?? ($user->media->url ?? null);
                if (!empty($photo) && !filter_var($photo, FILTER_VALIDATE_URL)) {
                    try {
                        $photo = Storage::url($photo);
                    } catch (\Throwable $e) {
                        $photo = url('/storage/' . ltrim($photo, '/'));
                    }
                }
            }
            $userRating = $ratingsByUser->get($user->id ?? null);
            return [
                'id' => $c->id,
                'body' => $c->body,
                'user' => [
                    'id' => $user->id ?? null,
                    'name' => $name,
                    'profile_photo' => $photo,
                ],
                'user_rating' => $userRating ? [
                    'id' => $userRating->id,
                    'rating_value' => (int) $userRating->rating_value,
                    'classification' => $userRating->classification,
                ] : null,
                'created_at' => optional($c->created_at)->format('Y-m-d'),
            ];
        })->values();
        return [
            'comments' => $result,
            'code' => 200
        ];
    }

    public function toggleLike(int $postId)
    {
        $user = Auth::user();
        if (! $user) {
            return ['message' => 'Unauthenticated', 'code' => 401];
        }

        $post = Post::find($postId);
        if (! $post) {
            return ['message' => 'Post not found', 'code' => 404];
        }

        if ($post->status !== 'Approved') {
            return ['message' => 'Cannot like a post that is not Approved', 'code' => 403];
        }

        return DB::transaction(function () use ($post, $user) {
            $existing = Like::where('post_id', $post->id)->where('user_id', $user->id)->first();
            if ($existing) {
                $existing->delete();
                $likesCount = $post->likes()->count();
                return ['message' => 'unliked', 'liked' => false, 'likes_count' => $likesCount, 'code' => 200];
            }

            Like::create(['post_id' => $post->id, 'user_id' => $user->id]);
            $likesCount = $post->likes()->count();
            return ['message' => 'liked', 'liked' => true, 'likes_count' => $likesCount, 'code' => 201];
        });
    }

   public function search($type, $sub)
    {
        $user = Auth::user();
        $isClient = !$user || $user->hasRole('client');
        $isAdmin = $user->hasRole('admin');
        switch ($type) {
            case 'event':
                $query = Event::where('name','LIKE',"%$sub%");
                if ($isClient) $query->where('date','>', now()) ->whereColumn('tickets', '>', 'reserved_tickets');
                $results = $query->get();
                $resource = EventResource::class;
                if($isAdmin) $results=[];
                break;
            case 'trip':
                $query = Trip::where('name','LIKE',"%$sub%");
                if ($isClient) $query->where('start_date','>', now()) ->whereColumn('tickets', '>', 'reserved_tickets');
                $results = $query->get();
                $resource = TripResource::class;
                if($isAdmin) $results=[];
                break;
            case 'tourist':
            case 'restaurant':
            case 'hotel':
                $results = Place::where('type', $type)
                    ->where('name','LIKE',"%$sub%")
                    ->get();
                $resource = PlaceResource::class;
                break;
            case 'article':
                $results = Article::where('title','LIKE',"%$sub%")->get();
                $resource = ArticleResource::class;
                if($isAdmin) $results=[];
                break;
            case 'company':
                if ($isClient) {
                    $results = AdminProfile::with('user')->where('name_of_company','LIKE',"%$sub%")->get()->pluck('user');
                    $resource = CompanyResource::class;
                }
                else  if($isAdmin) $results=[];
                else{
                    $results = AdminProfile::with('user')->where('name_of_company','LIKE',"%$sub%")->get();
                    $resource=AdminProfileResource::class;
                }
                break;
            case 'post':
                $results = Post::where('description','LIKE',"%$sub%")->get();
                $resource = PostResource::class;
                if($isAdmin) $results=[];
                break;
            case 'user':
                $results = Profile::where('first_name', 'LIKE', "%$sub%")->orWhere('last_name', 'LIKE', "%$sub%")->get();
                $results = $results->map(fn($profile) => $profile->user);
                $resource = UserResource::class;
                if($isClient||$isAdmin) $results=[];
                break;
            default:
                return ['results'=>[], 'message'=>'invalid type', 'code'=>400];
        }

        return [
            'results' => $resource::collection($results),
            'message' => 'this is all results',
            'code'    => 200,
        ];

    }


}
