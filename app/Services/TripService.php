<?php

namespace App\Services;

use App\Http\Resources\Trip\AllTripResource;
use App\Http\Resources\Trip\AllTripsResource;
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
use Illuminate\Support\Facades\DB;

class TripService
{
    use AuthorizesRequests;
    public function index()
    {
        $user=Auth::user();
        if(!$user||$user->hasRole('client'))
        {
            $tag=request()->query('tag');
            if($tag=="الكل")
            $trips=AllTripsResource::collection(Trip::where('start_date','>',now())->whereColumn('tickets', '>', 'reserved_tickets')->get());
            else{
                $tagName = TagName::where('body', $tag)->where('follow_to', 'trip')->first();
                if ($tagName) {
                    $tagIds = $tagName->tags()->whereNotNull('trip_id')->pluck('trip_id');
                    $trips = AllTripsResource::collection(Trip::whereIn('id', $tagIds)->where('start_date','>',now())->whereColumn('tickets', '>', 'reserved_tickets')->get());
                } else {
                    $trips = [];
                }
            }
        }
        else if($user->hasRole('super_admin'))
        {
            $trips=AllTripsResource::collection(Trip::all());
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
        $duration="";
        if(isset($request['days']))
            $duration=$request['days'].'day';
        if(isset($request['hours']))
            $duration=$duration." ".$request['hours'].'hours';
        if($duration=="")
        {
            return ['trip'=>null,'message'=>'the days field or hours field is required','code'=>400];
        }
        $trip =Auth::user()->trips()->create(['duration'=>$duration,...$request]);
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
                            "longitude"=>$section['longitude']??null,
                            "latitude"=>$section['latitude']??null,
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
         $duration="";
        if(isset($request['days']))
            $duration=$request['days'].'day';
        if(isset($request['hours']))
            $duration=$duration." ".$request['hours'].'hours';
        if($duration=="")
            $duration=$trip->duration;
        $trip->update(['duration'=>$duration,...$request]);
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
                            "longitude"=>$section['longitude']??null,
                            "latitude"=>$section['latitude']??null,
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
            $trip->user->adminProfile->decrement('number_of_trips');
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

        $user=Auth::user();
        if(!$user||$user->hasRole('client'))
        {
            $tag=request()->query('tag');
            if($tag=="الكل")
                $trips=Trip::where('user_id',$id)->where('start_date','>',now())->whereColumn('tickets', '>', 'reserved_tickets')->get();
            else{
                $tagName = TagName::where('body', $tag)->where('follow_to', 'trip')->first();
                if ($tagName) {
                    $tagIds = $tagName->tags()->whereNotNull('trip_id')->pluck('trip_id');
                    $trips = Trip::whereIn('id', $tagIds)->where('user_id',$id)->where('start_date','>',now())->whewhereColumn('tickets', '>', 'reserved_tickets')->get();
                } else {
                    $trips = [];
                }
            }
        }
        else
            $trips=AllTripsResource::collection(Trip::all());
        if ($trips) {
            $trips = AllTripsResource::collection($trips);
            $code = 200;
            $message = 'this is all trips for company'.$id;
        } else {
            $trips = null;
            $code = 404;
            $message = 'not found any trip for company'.$id;
        }
        return ['trips'=>$trips,'message'=>$message,'code'=>$code];
    }
    public function offers()
    {
        $trips=Trip::where('discount','>',0)->where('start_date','>',now())->whereColumn('tickets', '>', 'reserved_tickets')->get();
        if ($trips) {
            $trips = AllTripsResource::collection($trips);
            $code = 200;
            $message = 'this is all trips that it has an offer';
        } else {
            $trip = null;
            $code = 404;
            $message = 'not found any trip with offer';
        }
        return ['trips'=>$trips,'message'=>$message,'code'=>$code];
    }

    public function similarTrips($id)
    {
        $currentTrip = Trip::with('tags')->find($id);
        if (!$currentTrip) {
            return [
                'trips' => null,
                'message' => 'Trip not found',
                'code' => 404
            ];
        }
        $tagIds = $currentTrip->tags->pluck('tag_name_id')->toArray();
        if (empty($tagIds)) {
            return [
                'trips' => null,
                'message' => 'No tags for this trip',
                'code' => 404
            ];
        }
        $similarTripsData = Trip::select(
                'trips.id',
                DB::raw('COUNT(tags.tag_name_id) as common_tags_count')
            )
            ->join('tags', 'trips.id', '=', 'tags.trip_id')
            ->where('trips.id', '!=', $id)
            ->whereIn('tags.tag_name_id', $tagIds)
            ->where('trips.start_date', '>', now())
            ->whereColumn('trips.tickets', '>', 'trips.reserved_tickets')
            ->groupBy('trips.id')
            ->orderByDesc('common_tags_count')
            ->limit(10)
            ->get();

        if ($similarTripsData->isEmpty()) {
            return [
                'trips' => null,
                'message' => 'No similar trips found',
                'code' => 404
            ];
        }
        $similarTrips = Trip::with(['tags.tagName', 'media', 'user'])
            ->whereIn('id', $similarTripsData->pluck('id'))
            ->get()
            ->sortByDesc(function ($trip) use ($similarTripsData) {
                return $similarTripsData->firstWhere('id', $trip->id)->common_tags_count ?? 0;
            })
            ->values();

        return [
            'trips' => AllTripsResource::collection($similarTrips),
            'message' => 'These are the similar trips',
            'code' => 200
        ];
    }

    public function reserve($request)
    {
        $user = Auth::user();
        $trip=Trip::find($request['trip_id']);
        if(!$trip)
            return['message'=>"this trip not found",'code'=>404];
        if($trip->discount!=0)
            $price=$trip->new_price;
        else
            $price=$trip->price;
        if($request['number_of_tickets']!=count($request['passengers']))
            return['message'=>"the number of tickets must be equal to size of passengers array",'code'=>400];
        $remainingTickets=$trip->tickets-$trip->reserved_tickets;
        if($request['number_of_tickets']>$remainingTickets)
            return['message'=>"the number of tickets not available",'code'=>400];
        $booking=$user->bookings()->create([
            'price'=>$price*$request['number_of_tickets'],
            ...$request
        ]);
        foreach($request['passengers'] as $passenger){
            $booking->passengers()->create($passenger);
        }
        return [
            'message' => 'please pay to confirm bookings',
            'code' => 201,
            'booking' => $booking
        ];
    }
}
