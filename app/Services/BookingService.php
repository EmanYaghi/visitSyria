<?php

namespace App\Services;

use App\Http\Resources\Event\ResesrvationEventResource;
use App\Http\Resources\ReservationResource;
use App\Http\Resources\Trip\ReservationTripResource;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Trip;
use App\Notifications\AccountActivated;
use Illuminate\Support\Facades\Auth;

class BookingService
{
    protected $stripe;

    public function __construct(StripePaymentService $stripe)
    {
        $this->stripe = $stripe;
    }
    public function reserve($request)
    {
        $user = Auth::user();
        $type=$request['type'];
        $request['is_paid']=false;
        if($type=='trip')
        {
            $trip=Trip::find($request['id']);
            if(Booking::where('user_id',$user->id)->where('trip_id',$trip->id)->first())
                return['message'=>"you already reserve this trip",'code'=>400];
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
            if(Booking::where('user_id',$user->id)->where('event_id',$event->id)->first())
                return['message'=>"you already reserve this event",'code'=>400];
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
            ],
        ];
    }


    public function myBookings()
    {
        $user = Auth::user();
        $type=request()->query('type');
        $bookings = $user->bookings()->whereNotNull($type.'_id')->get();
        if ($bookings->isEmpty()) {
             return [
                'bookings'   => null,
                'message' => 'No '.$type.' reserved.',
                'code'    => 404,
            ];
        }
        $b= ReservationResource::collection($bookings);
         return [
            'bookings'   => $b,
            'message' => 'All reserved '.$type.' retrieved.',
            'code'    => 200,
        ];

    }
}

