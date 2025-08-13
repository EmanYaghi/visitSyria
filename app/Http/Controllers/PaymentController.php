<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Services\StripePaymentService;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected $stripe;
    public function __construct(StripePaymentService $stripe)
    {
        $this->stripe = $stripe;
    }
    public function pay(Request $request)
    {
        $request->validate([
            'stripeToken' => 'required|string',
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);
        $user = $request->user();
        $booking = Booking::findOrFail($request->booking_id);
        if($user->id!=$booking->user_id)
            return response()->json([
                'unauthorized'
            ],403);
        if($booking->trip_id!=null&&($booking->trip->tickets-$booking->trip->reserved_tickets<$booking->number_of_tickets)||
            $booking->event_id!=null&&($booking->event->tickets-$booking->event->reserved_tickets<$booking->number_of_tickets))
        {
            return response()->json(['message'=>'the tickets not valid']);
        }
        $data = $this->stripe->pay($user, $booking, $request->stripeToken);
        if($booking->payment_status=='succeeded'&&$booking->trip_id!=null)
        {
            $res=$booking->trip->reserved_tickets;
            $booking->trip->update(['reserved_tickets'=>$res+$booking->number_of_tickets]);
        }
        else if($booking->payment_status=='succeeded'&&$booking->event_id!=null)
        {
            $res=$booking->event->reserved_tickets;
            $booking->event->update(['reserved_tickets'=>$res+$booking->number_of_tickets]);
        }
        return response()->json([
            'message'=>$data['message'],
            'booking'=>$data['booking']??null,
        ]);
    }

    public function refund($id)
    {
        $booking = Booking::findOrFail($id);
        $user=Auth::user();
         if($user->id!=$booking->user_id)
            return response()->json([
                'unauthorized'
            ],403);
        if ($booking->payment_status!='succeeded') {
            return response()->json(['error' => 'No payment found'], 404);
        }
        $data = $this->stripe->refund($booking);
        if($booking->payment_status=='refunded'&&$booking->trip_id!=null)
        {
            $res=$booking->trip->reserved_tickets;
            $booking->trip->update(['reserved_tickets'=>$res-$booking->number_of_tickets]);
        }
        else if($booking->payment_status=='refunded'&&$booking->event_id!=null)
        {
            $res=$booking->event->reserved_tickets;
            $booking->event->update(['reserved_tickets'=>$res-$booking->number_of_tickets]);
        }
        return response()->json([
            'message'=>$data['message'],
            'booking'  => $data['booking']??null,
        ]);
    }
}
