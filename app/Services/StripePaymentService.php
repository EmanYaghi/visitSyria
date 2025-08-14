<?php

namespace App\Services;

use App\Models\Booking;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\SetupIntent;
use Stripe\PaymentIntent;
use Stripe\Refund;
use App\Models\User;
use App\Models\Payment;
use App\Models\CreditCard;
use Stripe\Charge;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function pay($user, Booking $booking, $token)
    {
        if($booking->is_paid==true)
            return['message'=>'this booking is paid'];
        try {

            $charge = Charge::create([
                'amount' => $booking->price * 100,
                'currency' => 'usd',
                'source' => $token,
                'description' => 'API Payment Example',
            ]);
            $booking->stripe_payment_id = $charge->id;
            $booking->payment_status     = $charge->status;
            if($charge->status=='succeeded')
            {
                $booking->is_paid=true;
            }
            $booking->save();
            return ['message'=>'you paid for this booking','booking'=>$booking];
        } catch (\Exception $e) {
            $booking->payment_status = 'failed';
            $booking->is_paid = false;
            $booking->save();
            return['message'=>$e->getMessage()];
        }
    }
    public function refund(Booking $booking)
    {
        try {
            $refund = Refund::create([
                'charge' => $booking->stripe_payment_id,
            ]);


            $booking->stripe_payment_id=null;
            if($refund->status=='succeeded'){
                $booking->payment_status ='refunded';
                $booking->is_paid=false;

            }
            $booking->save();
            return ['message'=>'you refund this booking','booking'=>$booking];
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
