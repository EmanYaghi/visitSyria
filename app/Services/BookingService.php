<?php

namespace App\Services;

use App\Http\Resources\Trip\AllTripsResource;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Flight;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;

class BookingService
{
        public function reserve($request)
    {
        $user = Auth::user();
        $type=$request['type'];
        $request['is_paid']=false;
        if($type=='trip')
        {
            $trip=Trip::find($request['id']);
            if($trip->start_date<=now())
                return['message'=>"the trip has ended",'code'=>400];
            if($request['number_of_tickets']!=count($request['passengers']))
                return['message'=>"the number of tickets must be equal to size of passengers array",'code'=>400];
            $remainingTickets=$trip->tickets-$trip->reserved_tickets;
            if($request['number_of_tickets']>$remainingTickets)
                return['message'=>"the number of tickets not available",'code'=>400];
            $request['price']=($trip->discount?$trip->new_price:$trip->price)*$request['number_of_tickets'];
        }
        else if($type=='event')
        {
            $event=Event::find($request['id']);
            if($event->date<=now())
                return['message'=>"the event has ended",'code'=>400];
            if($request['number_of_tickets']!=count($request['passengers']))
                return['message'=>"the number of tickets must be equal to size of passengers array",'code'=>400];
            if($event->event_type=="limited"){
                $remainingTickets=$event->tickets-$event->reserved_tickets;
                if($request['number_of_tickets']>$remainingTickets)
                    return['message'=>"the number of tickets not available",'code'=>400];
            }
            if($event->price_type=="free"){
                $request['price']=0;
                $request['is_paid']=true;
            }else{
                $request['price']=$event->price*$request['number_of_tickets'];
            }
        }

        $booking=$user->bookings()->create([
            $type.'_id'=>$request['id'],
            ...$request
        ]);
        foreach($request['passengers'] as $passenger)
            $booking->passengers()->create($passenger);
        if($request['price']==0)
             return [
                'message' => 'your booking has been completed successfully',
                'code' => 201,
                'booking' => [
                    'id'=>$booking->id,
                    'is_paid'=>$booking->is_paid,
                    'price'=>$booking->price,
                ]
            ];
        return [
            'message' => 'please pay to confirm bookings',
            'code' => 201,
            'booking' => [
                'id'=>$booking->id,
                'is_paid'=>$booking->is_paid,
                'price'=>$booking->price,
            ]
        ];
    }

    public function cancelReservation($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return ['message' => 'Booking not found.', 'code' => 404];
        }
        if($booking->is_paid)
            return ['message' => 'you can not cancel this booking because you are paid', 'code' => 404];
        $booking->delete();
        return ['message' => 'booking cancelled.', 'code' => 200];
    }

    public function myReservations($type)
    {
        $user = Auth::user();
        $bookings = $user->bookings()->whereNotNull($type.'_id')->with($type)->get()->pluck($type);

        if ($bookings->isEmpty()) {
             return [
                'bookings'   => null,
                'message' => 'No trips reserved.',
                'code'    => 404,
            ];
        }
        if($type=='trip')
            $b= AllTripsResource::collection($bookings);
        else if($type=='event')
            $b= AllEventsResource::collection($bookings);
        else if($type=='flight')
            $b=AllFlightsResource::collection($bookings);
         return [
            'bookings'   => $b,
            'message' => 'All reserved '.$type.' retrieved.',
            'code'    => 200,
        ];

    }
}
