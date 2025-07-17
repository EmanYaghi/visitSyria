<?php

namespace App\Services;

use App\Http\Resources\Trip\AllTripResource;
use App\Http\Resources\Trip\TripResource;
use App\Models\TagName;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class TripService
{
    protected function checkAuthorization()
    {
        if (! Auth::user()->hasRole('admin')) {
            throw new UnauthorizedHttpException('', 'Unauthorized action.');
        }
    }

    public function index()
    {
        $trips=AllTripResource::collection(Trip::all());
        $code=200;
        $message='this is all trips ';
        return ['trips'=>$trips,'message'=>$message,'code'=>$code];
    }


    public function store($request)
    {
        $this->checkAuthorization();
        $trip =Auth::user()->trips()->create($request);
        /*$imageUrls = [];
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $imageUrls[] = $image->store('trips', 'public');
            }
        }
        foreach ($imageUrls as $url) {
            $trip->media()->create([
                'url' => $url,
            ]);
        }
        foreach ($request->timeline as $timeline) {
            $timeline=$trip->timelines()->create(['day' => $timeline['day']]);
            foreach ($timeline['section'] as $section) {
                $timeline->sections()->create([
                    'time' => $section['time'],
                    'title' => $section['title'],
                    'description' => $section['description'],
                ]);
            }
        }
        foreach ($request->tags as $tag) {
            $trip->tags()->create([
                'tag_name_id'=>TagName::where('body',$tag)->id(),
            ]);
        }*/
        $code=201;
        $message='trip created';
        return ['message'=>$message,'code'=>$code];
    }


    public function show($id)
    {
        $t=Trip::find($id);
        if($t){
            $trip=new TripResource($t);
            $code=200;
            $message='trip founded';
        }else{
            $code=404;
            $message='not found';
        }
        return ['trip'=>$trip,'message'=>$message,'code'=>$code];
    }
    public function update($request, $id)
    {
        $trip=Trip::update($request);
        $trip->save();
        $code=201;
        $message='trip updated';
        return ['message'=>$message,'code'=>$code];
    }
    public function destroy( $id)
    {
        $trip=Trip::find($id);
        $trip->delete();
        $code=201;
        $message='trip deletsd';
        return ['message'=>$message,'code'=>$code];
    }
    public function companyTrips( $id)
    {
        $trips=AllTripResource::collection(Trip::where('user_id',$id)->get());
        $code=200;
        $message='this is all trips for company'.$id;
        return ['trips'=>$trips,'message'=>$message,'code'=>$code];
    }
    public function offers()
    {
        $trips=AllTripResource::collection(Trip::where('discount','!=',null)->get());
        $code=200;
        $message='this is all trips that it has an offer';
        return ['trips'=>$trips,'message'=>$message,'code'=>$code];
    }
}
