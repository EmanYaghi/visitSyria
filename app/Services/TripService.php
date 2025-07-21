<?php

namespace App\Services;

use App\Http\Resources\Trip\AllTripResource;
use App\Http\Resources\Trip\ReservationTripResource;
use App\Http\Resources\Trip\TripResource;
use App\Models\TagName;
use App\Models\Timeline;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TripService
{
    use AuthorizesRequests;
    public function index()
    {
        $tag=request()->query('tag');
        if($tag=="الكل")
            $trips=TripResource::collection(Trip::all());
        else{
            $tagName = TagName::where('body', $tag)->where('follow_to', 'trip')->first();
            if ($tagName) {
                $tagIds = $tagName->tags()->whereNotNull('trip_id')->pluck('trip_id');
                $trips = TripResource::collection(Trip::whereIn('id', $tagIds)->get());
            } else {
                $trips = [];
            }
        }
        $code=200;
        $message='this is all trips ';
        return ['trips'=>$trips,'message'=>$message,'code'=>$code];
    }
    public function store($request)
    {
        $this->authorize('create', Trip::class);
        if (isset($request['improvements']) && is_array($request['improvements'])) {
            $request['improvements'] = json_encode($request['improvements']);
        }
        $trip =Auth::user()->trips()->create($request);
        if($request['new_price']!=null)
            $trip->update(['discount'=>100-100*$request['new_price']/$request['price']]);
        if (isset($request['tags'])) {
            foreach ($request['tags'] as $tag) {
                $tagName=TagName::where('body',$tag)->where('follow_to','trip')->first();
                $trip->tags()->create(["tag_name_id" => $tagName->id]);
            }
        }
        if (isset($request['images']) && is_array($request['images'])) {
            foreach ($request['images'] as $image) {
                if ($image instanceof \Illuminate\Http\UploadedFile) {
                    $url = $image->store('trip_images');
                    $trip->media()->create(['url' => $url]);
                }
            }
        }

        if (isset($request['timelines']) ) {

            foreach ($request['timelines'] as $timelineData) {
                $timeline = $trip->timelines()->create([
                    "day_number" => $timelineData['day'] ?? null
                ]);

                if (isset($timelineData['sections']) ) {
                    foreach ($timelineData['sections'] as $section) {
                        $timeline->sections()->create([
                            "time" => $section['time'] ?? null,
                            "title" => $section['title'] ?? null,
                            "description" => $section['description'] ?? null,
                        ]);
                    }
                }
            }
        }
        $trip->user->adminProfile->increment('number_of_trips');
        $code=201;
        $message='trip created';
        return ['trip'=>new TripResource($trip),'message'=>$message,'code'=>$code];
    }
    public function show($id)
    {
        $t=Trip::find($id);
        if($t){
            $trip=new TripResource($t);
            $code=200;
            $message='trip founded';
        }else{
            $trip=null;
            $code=404;
            $message='not found';
        }
        return ['trip'=>$trip,'message'=>$message,'code'=>$code];
    }
    public function update($request, $id)
    {
        $trip = Trip::findOrFail($id);
        $this->authorize('update', $trip);
        $trip->update($request);
        if($request['new_price']!=null)
            $trip->update(['discount'=>100-100*$request['new_price']/$trip->price]);
        $trip->save();
        if (isset($request['improvements']) && is_array($request['improvements'])) {
            $request['improvements'] = json_encode($request['improvements']);
        }
        if (isset($request['tags'])) {
            $trip->tags()->delete();
            foreach ($request['tags'] as $tag) {
                $tagName=TagName::where('body',$tag)->where('follow_to','trip')->first();
                $trip->tags()->create(["tag_name_id" => $tagName->id]);
            }
        }
        if (isset($request['images']) && is_array($request['images'])) {
            $trip->media()->delete();
            foreach ($request['images'] as $image) {
                if ($image instanceof \Illuminate\Http\UploadedFile) {
                    $url = $image->store('trip_images');
                    $trip->media()->create(['url' => $url]);
                }
            }
        }

        if (isset($request['timelines']) ) {
            $trip->timelines()->delete();
            foreach ($request['timelines'] as $timelineData) {
                $timeline = $trip->timelines()->create([
                    "day_number" => $timelineData['day'] ?? null
                ]);

                if (isset($timelineData['sections']) ) {
                    foreach ($timelineData['sections'] as $section) {
                        $timeline->sections()->create([
                            "time" => $section['time'] ?? null,
                            "title" => $section['title'] ?? null,
                            "description" => $section['description'] ?? null,
                        ]);
                    }
                }
            }
        }
        $code=201;
        $message='trip updated';
        return ['message'=>$message,'code'=>$code];
    }
    public function destroy( $id)
    {
        $trip=Trip::find($id);
        $this->authorize('delete', $trip);
        if($trip){
            $trip->delete();
            $code=201;
            $message='trip deleted';
        }
        else{
            $code=404;
            $message='trip not found';
        }
        return ['message'=>$message,'code'=>$code];
    }
    public function companyTrips( $id)
    {
        $trips=TripResource::collection(Trip::where('user_id',$id)->get());
        $code=200;
        $message='this is all trips for company'.$id;
        return ['trips'=>$trips,'message'=>$message,'code'=>$code];
    }
    public function offers()
    {
        $trips=Trip::where('discount','>',0)->get();
        if ($trips) {
            $trips = TripResource::collection($trips);
            $code = 200;
            $message = 'this is all trips that it has an offer';
        } else {
            $trip = null;
            $code = 404;
            $message = 'not found any trip with offer';
        }
        return ['trips'=>$trips,'message'=>$message,'code'=>$code];
    }
}
