<?php

namespace App\Services;

use App\Http\Resources\Trip\AllTripsResource;
use App\Http\Resources\Trip\TripResource;
use App\Http\Resources\Trip\TripWithFeedbackResource;
use App\Jobs\SendNotificationJob;
use App\Models\TagName;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class TripService
{
    use AuthorizesRequests;

    public function index()
    {
        $tokenPresent = (bool) request()->bearerToken();
        $user = $tokenPresent ? Auth::user() : null;

        if (!$user || ($user && $user->hasRole('client'))) {
            $tag = request()->query('tag');
            if ($tag == "الكل") {
                $trips = Trip::where('start_date', '>', now())
                    ->whereColumn('tickets', '>', 'reserved_tickets')
                    ->get();
            } else {
                $tagName = TagName::where('body', $tag)->where('follow_to', 'trip')->first();
                if ($tagName) {
                    $tagIds = $tagName->tags()->whereNotNull('trip_id')->pluck('trip_id');
                    $trips = Trip::whereIn('id', $tagIds)
                        ->where('start_date', '>', now())
                        ->whereColumn('tickets', '>', 'reserved_tickets')
                        ->get();
                } else {
                    $trips = collect([]);
                }
            }

            if ($user) {
                $trips = $trips->filter(function ($trip) use ($user) {
                    return !$trip->bookings()->where('user_id', $user->id)->exists();
                })->values();
            }
            $trips=TripResource::collection($trips);
        } else if ($user->hasRole('super_admin')) {
        {
            $trips = Trip::all();
            $trips=TripWithFeedbackResource::collection($trips);
        }
        } else if ($user->hasRole('admin')) {
            $tag = request()->query('tag');
             if ($tag == "الكل") {
                $trips = Trip::where('user_id', $user->id)->get();
            } else {
                $tagName = TagName::where('body', $tag)->where('follow_to', 'trip')->first();
                if ($tagName) {
                    $tagIds = $tagName->tags()->whereNotNull('trip_id')->pluck('trip_id');
                    $trips = Trip::whereIn('id', $tagIds)
                        ->where('user_id', $user->id)
                        ->get();
                } else {
                    $trips = collect([]);
                }
            }
            $trips=TripWithFeedbackResource::collection($trips);
        } else {
            $trips = collect([]);
        }

        $code = 200;
        $message = 'this is all trips ';
        return ['trips' => $trips, 'message' => $message, 'code' => $code];
    }

    public function store($request)
    {
        $this->authorize('create', Trip::class);
        if (isset($request['improvements']) && is_array($request['improvements'])) {
            $request['improvements'] = json_encode($request['improvements']);
        }
        $trip = Auth::user()->trips()->create($request);
        if ($request['discount'] != null)
            $trip->update(['new_price' => $request['price'] - $request['price'] * $request['discount'] / 100]);
        if (isset($request['tags'])) {
            foreach ($request['tags'] as $tag) {
                $tagName = TagName::where('body', $tag)->where('follow_to', 'trip')->first();
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

        if (isset($request['timelines'])) {

            foreach ($request['timelines'] as $timelineData) {
                $timeline = $trip->timelines()->create([
                    "day_number" => $timelineData['day'] ?? null
                ]);

                if (isset($timelineData['sections'])) {
                    foreach ($timelineData['sections'] as $section) {
                        $timeline->sections()->create([
                            "time" => $section['time'] ?? null,
                            "title" => $section['title'] ?? null,
                            "description" => $section['description'] ?? null,
                            "longitude" => $section['longitude'] ?? null,
                            "latitude" => $section['latitude'] ?? null,
                        ]);
                    }
                }
            }
        }
        $trip->user->adminProfile->increment('number_of_trips');
        $code = 201;
        $message = 'trip created';
        return ['trip' => new TripResource($trip), 'message' => $message, 'code' => $code];
    }

    public function show($id)
    {
        $t = Trip::find($id);
        if ($t) {
            $trip=new TripWithFeedbackResource($t);
            $code = 200;
            $message = 'trip founded';
        } else {
            $trip = null;
            $code = 404;
            $message = 'not found';
        }
        return ['trip' => $trip, 'message' => $message, 'code' => $code];
    }

    public function update($request, $id)
    {
        $trip = Trip::findOrFail($id);
        $this->authorize('update', $trip);
        $trip->update($request);
        if (isset($request['images']) && is_array($request['images'])) {
             $keep = collect($request['old_images'] ?? [])
                ->map(function ($item) {
                    if (empty($item)) return null;
                    $path = parse_url($item, PHP_URL_PATH) ?: $item;
                    $path = ltrim($path, '/');
                    if (Str::startsWith($path, 'storage/')) {
                        $path = Str::after($path, 'storage/');
                    }
                    return $path;
                })
                ->filter()
                ->values()
                ->all();
            if (!empty($request['old_images'])) {
                foreach ($trip->media as $media) {
                    if (!in_array($media->url, $keep, true)) {
                        Storage::disk('public')->delete($media->url);
                        $media->delete();
                    }
                }
            }
            foreach ($request['images'] as $image) {
                if ($image instanceof \Illuminate\Http\UploadedFile) {
                    $url = $image->store('trip_images');
                    $trip->media()->create(['url' => $url]);
                }
            }
        }
        $code = 201;
        $message = 'trip updated';
        return ['message' => $message, 'code' => $code];
    }

    public function companyTrips($id)
    {
        $tokenPresent = (bool) request()->bearerToken();
        $user = $tokenPresent ? Auth::user() : null;
        if (!$user || ($user && $user->hasRole('client'))) {
            $tag = request()->query('tag');
            if ($tag == "الكل") {
                $trips = Trip::where('user_id', $id)
                    ->where('start_date', '>', now())
                    ->whereColumn('tickets', '>', 'reserved_tickets')
                    ->get();
            } else {
                $tagName = TagName::where('body', $tag)->where('follow_to', 'trip')->first();
                if ($tagName) {
                    $tagIds = $tagName->tags()->whereNotNull('trip_id')->pluck('trip_id');
                    $trips = Trip::whereIn('id', $tagIds)
                        ->where('user_id', $id)
                        ->where('start_date', '>', now())
                        ->whereColumn('tickets', '>', 'reserved_tickets')
                        ->get();
                } else {
                    $trips = collect([]);
                }
            }

            if ($user) {
                $trips = $trips->filter(function ($trip) use ($user) {
                    return !$trip->bookings()->where('user_id', $user->id)->exists();
                })->values();
            }
            $trips = TripResource::collection($trips);
        } else {
            $trips = Trip::where('user_id', $id)->get();
            $trips=TripWithFeedbackResource::collection($trips);
        }

        if ($trips) {
            $trips = $trips;
            $code = 200;
            $message = 'this is all trips for company' . $id;
        } else {
            $trips = [];
            $code = 200;
            $message = 'not found any trip for company' . $id;
        }
        return ['trips' => $trips, 'message' => $message, 'code' => $code];
    }

    public function offers()
    {
        $tokenPresent = (bool) request()->bearerToken();
        $user = $tokenPresent ? Auth::user() : null;

        $trips = Trip::where('discount', '>', 0)
            ->where('start_date', '>', now())
            ->whereColumn('tickets', '>', 'reserved_tickets')
            ->get();

        if ($user) {
            $trips = $trips->filter(function ($trip) use ($user) {
                return !$trip->bookings()->where('user_id', $user->id)->exists();
            })->values();
        }

        if ($trips) {
            $trips = TripResource::collection($trips);
            $code = 200;
            $message = 'this is all trips that it has an offer';
        } else {
            $trips = [];
            $code = 200;
            $message = 'not found any trip with offer';
        }
        return ['trips' => $trips, 'message' => $message, 'code' => $code];
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
                'trips' => [],
                'message' => 'No tags for this trip',
                'code' => 200
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
            ->limit(20)
            ->get();

        if ($similarTripsData->isEmpty()) {
            return [
                'trips' => [],
                'message' => 'No similar trips found',
                'code' => 200
            ];
        }
        $similarTrips = Trip::with(['tags.tagName', 'media', 'user'])
            ->whereIn('id', $similarTripsData->pluck('id'))
            ->get()
            ->sortByDesc(function ($trip) use ($similarTripsData) {
                return $similarTripsData->firstWhere('id', $trip->id)->common_tags_count ?? 0;
            })
            ->values();

        $tokenPresent = (bool) request()->bearerToken();
        $user = $tokenPresent ? Auth::user() : null;

        if ($user) {
            $similarTrips = $similarTrips->filter(function ($trip) use ($user) {
                return !$trip->bookings()->where('user_id', $user->id)->exists();
            })->values();
        }
        return [
            'trips' => TripResource::collection($similarTrips),
            'message' => 'These are the similar trips',
            'code' => 200
        ];
    }

    public function cancel($id)
    {
        $trip = Trip::find($id);
        $this->authorize('delete', $trip);
        if ($trip && $trip->status == "لم تبدأ بعد" && now()->diffInDays(\Carbon\Carbon::parse($trip->start_date)) >= 3) {
            foreach ($trip->bookings() as $booking) {
                if ($booking->is_paid == true) {
                    $user = $booking->user;
                    SendNotificationJob::dispatch($user,'alert', 'the trip '.$trip->name.' was caceled and your money was refunded');
                    $stripe=new StripePaymentService();
                    $stripe->refund($booking);
                }
            }
            $trip->update(['status' => 'تم الالغاء']);
            $code = 201;
            $message = 'trip canceled';
        } else {
            $code = 404;
            $message = 'trip not found or trip finished';
        }
        return ['message' => $message, 'code' => $code];
    }

    public function destroy($id)
    {
        $trip = Trip::find($id);
        $this->authorize('delete', $trip);
        if ($trip && ($trip->status == "تم الالغاء")) {
            $trip->delete();
            $trip->user->adminProfile->decrement('number_of_trips');
            $code = 201;
            $message = 'trip deleted';
        } else {
            $code = 404;
            $message = 'trip not found or trip dont canceled';
        }
        return ['message' => $message, 'code' => $code];
    }

    public function lastTrip()
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin'))
            return ['message' => 'unauthorized', 'code' => 403];
        $trips = Trip::orderByDesc('created_at')->take(6)->get();
        return ['trips' => TripWithFeedbackResource::collection($trips), 'message' => 'this is last 6 trips', 'code' => 200];
    }

}
