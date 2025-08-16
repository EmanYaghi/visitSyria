<?php

namespace App\Services;

use App\Http\Resources\Trip\TripResource;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Place;
use App\Models\Post;
use App\Models\Rating;
use App\Models\Save;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FeedbackService
{
    public function setSave($id)
    {
        $type=request()->query('type');
        $user=Auth::user();
        $code=201;
        $message='added to saves';
        if(Trip::find($id)&&$type=="trip"&&!Save::where('user_id',$user->id)->where('trip_id',$id)->first()){
            $user->saves()->create(["trip_id"=>$id]);
        }
        else if(Post::find($id)&&$type=="post"&&!Save::where('user_id',$user->id)->where('post_id',$id)->first()){
            $user->saves()->create(["post_id"=>$id]);
        }
        else if(Place::find($id)&&$type=="place"&&!Save::where('user_id',$user->id)->where('place_id',$id)->first()){
            $user->saves()->create(["place_id"=>$id]);
        }
        else if(Article::find($id)&&$type=="article"&&!Save::where('user_id',$user->id)->where('article_id',$id)->first()){
            $user->saves()->create(["article_id"=>$id]);
        }
        else if(Event::find($id)&&$type=="event"&&!Save::where('user_id',$user->id)->where('event_id',$id)->first()){
            $user->saves()->create(["event_id"=>$id]);
        }
        else{
            $code=404;
            $message='not found or pre saved';
        }

        return ['message'=>$message,'code'=>$code];
    }

    public function deleteSave($id)
    {
        $type=request()->query('type');
        $user=Auth::user();
        $code=200;
        $message='deleted from saves';
        $save=null;
        if(Trip::find($id)&&$type=="trip"){
            $save=Save::where('user_id',$user->id)->where('trip_id',$id)->first();
        }
        else if(Post::find($id)&&$type=="post"){
            $save=Save::where('user_id',$user->id)->where('post_id',$id)->first();
        }
        else if(Place::find($id)&&$type=="place"){
            $save=Save::where('user_id',$user->id)->where('place_id',$id)->first();
        }
        else if(Article::find($id)&&$type=="article"){
           $save=Save::where('user_id',$user->id)->where('article_id',$id)->first();
        }
        else if(Event::find($id)&&$type=="event"){
            $save=Save::where('user_id',$user->id)->where('event_id',$id)->first();
        }
        if($save){
            $save->delete();
        }
        else{
            $code=404;
            $message='not found';
        }
        return ['message'=>$message,'code'=>$code];
    }

    public function getSaves()
    {
        $type=request()->query('type');
        $user = Auth::user();
        $saves=null;
        if($type=="trip"){
            $trips = $user->saves()->whereNotNull('trip_id')->with('trip')->get()->pluck('trip');
            $saves = TripResource::collection($trips);
        }
        else if($type=="post"){
            $posts = $user->saves()->whereNotNull('post_id')->with('post')->get()->pluck('post');
            $saves = $posts;
        }
        else if($type=="restaurant"){
            $places = $user->saves()->whereNotNull('place_id')->with('place')->get()->pluck('place');
            $places = $places->filter(fn($place) => $place->type === 'restaurant');
            $saves = $places;
        }
        else if($type=="hotel"){
            $places = $user->saves()->whereNotNull('place_id')->with('place')->get()->pluck('place');
            $places = $places->filter(fn($place) => $place->type === 'hotel');
            $saves = $places;
        }
        else if($type=="tourist"){
            $places = $user->saves()->whereNotNull('place_id')->with('place')->get()->pluck('place');
            $places = $places->filter(fn($place) => $place->type === 'tourist');
            $saves = $places;
        }
        else if($type=="article"){
            $articles = $user->saves()->whereNotNull('article_id')->with('article')->get()->pluck('article');
            $saves = $articles;
        }
        else if($type=="event"){
            $events = $user->saves()->whereNotNull('event_id')->with('event')->get()->pluck('event');
            $saves =$events;
        }
        return ['saves'=>$saves];
    }

    public function setRating( $request,$id)
    {
        $user = Auth::user();
        $type = request()->query('type');
        $ratingValue = $request['rating_value'];
        if($ratingValue>=3)
            $classification="positive";
        else
            $classification="negative";
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
            $rate->update(['rating_value'=>$ratingValue,'classification'=>$classification]);
            $rate->save();
        } else {
            $user->ratings()->create([$column=> $id,'rating_value' => $ratingValue,'classification'=>$classification]);
        }

        return ["message" => $message,"code"=> $code];
    }

    public function deleteRating($id)
    {
        $user = Auth::user();
        $type = request()->query('type');

        $message="delet rating successfully";
        $code=200;
        $user = Auth::user();
        $type=request()->query('type');
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
        return ["message"=>$message,"code"=>$code];
    }

    public function setComment($request,$id)
    {
        $user = Auth::user();
        $type = request()->query('type');
        $message = "set comment successfully";
        $code = 201;
        $allowedTypes = [
            'trip'  => 'trip_id',
            'place' => 'place_id',
            'post'=>'post_id'
        ];
        if (!isset($allowedTypes[$type])) {
            return [
                "message" => "the type is not correct",
                "code"    => 400
            ];
        }
        $column = $allowedTypes[$type];
        $comment=$user->comments()->create([$column=> $id,'body' => $request['body']]);
        return ["comment"=>$comment,"message"=>$message,"code"=>$code];
    }

    public function deleteComment($id)
    {
        $comment=Comment::find($id);
        if(!$comment){
            $message = "comment not found";
            $code = 404;
        }else{
            $comment->delete();
            $message = "delete comment successfully";
            $code = 200;
        }
        return ["message"=>$message,"code"=>$code];
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
    if (in_array($type, ['trip','place'], true) && !empty($userIds)) {
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
                $last  = trim((string)($user->profile->last_name ?? ''));
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
                'rating_value' => (int)$userRating->rating_value,
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

}
