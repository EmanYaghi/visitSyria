<?php

namespace App\Services;

class BookingService
{
    public function reserve($request,$id)
    {

    }
    public function cancelReservation($id)
    {

    }
    public function myReservedTrips()
    {
        $user = Auth::user();
        $trips = $user->bookings()->whereNotNull('trip_id')->with('trip')->get()->pluck('trip');
        if ($trips) {
            $trips = ReservationTripResource::collection($trips);
            $code = 200;
            $message = 'this is all trips that you reserved it';
        } else {
            $trip = null;
            $code = 404;
            $message = 'not found any trip reserved';
        }
        return ['trips'=>$trips,'message'=>$message,'code'=>$code];
    }
}
