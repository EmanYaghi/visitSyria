<?php

namespace App\Services;

use App\Http\Resources\Event\ResesrvationEventResource;
use App\Http\Resources\PersonResource;
use App\Http\Resources\ReservationResource;
use App\Http\Resources\Trip\ReservationTripResource;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Flight;
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
    public function bookFlight($request)
    {
        $user = Auth::user();
        if (!$user->hasRole('client')) {
            return [
                'message' => "unauthorized",
                'code'    => 403
            ];
        }
        $request['is_paid']=false;
        $request['number_of_tickets']=$request['number_of_adults']+$request['number_of_children']+$request['number_of_infants'];
        $bookings = Booking::where('user_id', $user->id)->get();
        foreach ($bookings as $booking) {
            if ($booking->flight_data == $request['flight_data']) {
                return [
                    'message' => "you already reserve this flight",
                    'code'    => 400
            ];
        }}

        if($request['number_of_tickets']!=count($request['passengers']))
            return['message'=>"the number of tickets must be equal to size of passengers array",'code'=>400];
        $remainingTickets=$request['flight_data']['seats_remaining'];
        if($request['number_of_tickets']>$remainingTickets)
            return['message'=>"the number of tickets not available",'code'=>400];
        $adults=$children=$infants=0;
        foreach ($request['passengers'] as $passenger) {
            $birthDate = \Carbon\Carbon::parse($passenger['birth_date']);
            if ($birthDate->diffInYears(now()) >= 18) {
                $adults++;
            } elseif ($birthDate->diffInYears(now()) < 2) {
                $infants++;
            } else {
                $children++;
            }
        }

        if($adults!=$request['number_of_adults']||$children!=$request['number_of_children']||$infants!=$request['number_of_infants'])
            return['message'=>"the birth_dates not equals with number of adults and children and infants",'code'=>400];
        $booking=$user->bookings()->create($request);
        foreach($request['passengers'] as $passenger)
            $booking->passengers()->create($passenger);
        $booking->price=$booking->flight_data['price_total'];
        $booking->save();
        return [
            'message' => 'please pay to confirm bookings',
            'code' => 201,
            'booking' => [
                'id'=>$booking->id,
                'price'=>$booking->price,
            ],
        ];
    }
    public function reserve($request)
    {
        $user = Auth::user();
        if (!$user->hasRole('client')) {
            return [
                'message' => "unauthorized",
                'code'    => 403
            ];
        }
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
        if (!$user->hasRole('client')) {
            return [
                'message' => "unauthorized",
                'code'    => 403
            ];
        }
        $type=request()->query('type');
        if($type=='flight')
            $bookings=$user->bookings()->whereNotNull('flight_data')->get();
        else
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

   public function person($id, $type)
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['admin', 'super_admin'])||(!$user->hasRole('super_admin')&&$type=='event')||($user->hasRole('admin')&&$type=='trip'&&Trip::find($id)->user->id!=$user->id)) {
            return [
                'message' => "unauthorized",
                'code'    => 403
            ];
        }
        $models = [
            'event'  => Event::class,
            'trip'   => Trip::class,
        ];
        if (!isset($models[$type])) {
            return [
                'message' => "type not valid",
                'code' => 400
            ];
        }
        $model = $models[$type]::with('bookings.user.profile')->find($id);
        if (!$model) {
            return [
                'message' => "$type not found",
                'code' => 400
            ];
        }
        $users = $model->bookings->pluck('user');
        return [
            'message' => 'this is all user that book this ' . $type,
            'code' => 200,
            'users' => PersonResource::collection($users)
        ];
    }


}

