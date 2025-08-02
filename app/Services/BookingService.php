<?php

namespace App\Services;

use App\Http\Resources\Trip\ReservationTripResource;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\Passenger;
use App\Models\Payment;
use App\Models\User;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Charge;
use Stripe\Refund;

class BookingService
{

        public function handlePayment($request)
    {
        $user = Auth::user();
        $booking = Booking::findOrFail($request->booking_id);

        Stripe::setApiKey(config('services.stripe.secret'));

        // 🧍‍♂️ Create or fetch Stripe customer
        if (!$user->stripe_customer_id) {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $request->name,
            ]);
            $user->stripe_customer_id = $customer->id;
            $user->save();
        }

        // 💳 Create PaymentMethod
        $paymentMethod = PaymentMethod::create([
            'type' => 'card',
            'card' => [
                'number' => $request->number,
                'exp_month' => $request->exp_month,
                'exp_year' => $request->exp_year,
                'cvc' => $request->cvc,
            ],
        ]);

        if ($request->save_card) {
            $paymentMethod->attach(['customer' => $user->stripe_customer_id]);
        }

        // 💰 Convert SYP to USD manually (مثلاً: 1 USD = 13500 SYP)
        $amountInUSD = ceil($booking->price / 13500 * 100); // Stripe uses cents

        $paymentIntent = PaymentIntent::create([
            'amount' => $amountInUSD,
            'currency' => 'usd',
            'customer' => $user->stripe_customer_id,
            'payment_method' => $paymentMethod->id,
            'off_session' => !$request->save_card ? false : true,
            'confirm' => true,
        ]);

        $booking->update(['is_paid' => true]);

        Payment::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'transaction_id' => $paymentIntent->id,
            'status' => $paymentIntent->status,
            'currency' => 'usd',
        ]);

        return ['message' => 'تمت العملية بنجاح', 'code' => 201];
    }

    public function refund($paymentIntentId)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $refund = Refund::create([
            'payment_intent' => $paymentIntentId,
        ]);

        return ['message' => 'تم استرجاع المبلغ', 'refund' => $refund, 'code' => 200];
    }




    public function saveCard(Request $request)
{
    $user = auth()->user();

    if (!$user->stripe_customer_id) {
        $customer = \Stripe\Customer::create([
            'email' => $user->email,
            'name' => $user->name,
        ]);
        $user->update(['stripe_customer_id' => $customer->id]);
    }

    $paymentMethod = $request->input('payment_method');

    \Stripe\PaymentMethod::attach($paymentMethod, [
        'customer' => $user->stripe_customer_id,
    ]);

    \Stripe\Customer::update($user->stripe_customer_id, [
        'invoice_settings' => ['default_payment_method' => $paymentMethod],
    ]);

    return response()->json(['message' => 'تم حفظ البطاقة بنجاح']);
}


\Stripe\PaymentIntent::create([
    'amount' => $amount * 100,
    'currency' => 'usd',
    'customer' => $user->stripe_customer_id,
    'payment_method' => $user->default_payment_method_id,
    'off_session' => true,
    'confirm' => true,
]);



\Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

$customer = \Stripe\Customer::create([
    'email' => $user->email,
    'name' => $user->name,
]);


$setupIntent = \Stripe\SetupIntent::create([
    'customer' => $customer->id,
]);


\Stripe\PaymentIntent::create([
    'amount' => 5000, // بالسنت، يعني $50
    'currency' => 'usd',
    'customer' => $customer->id,
    'payment_method' => $saved_payment_method_id,
    'off_session' => true,
    'confirm' => true,
]);


$methods = \Stripe\PaymentMethod::all([
    'customer' => $customer->id,
    'type' => 'card',
]);

$defaultCard = $methods->data[0];


$card = new CreditCard();
$card->user_id = $user->id;
$card->card_holder = $defaultCard->billing_details->name;
$card->card_number = '**** **** **** ' . $defaultCard->card->last4;
$card->expiry_date = $defaultCard->card->exp_month . '/' . $defaultCard->card->exp_year;
$card->stripe_payment_method_id = $defaultCard->id;
$card->save();



    public function pay($id,$request)
    {
        Payment::create([
            'user_id' => auth()->id(),
            'booking_id' => $request->booking_id,
            'transaction_id' => $charge->id,
            'status' => $charge->status === 'succeeded' ? 'completed' : 'failed',
            'currency' => 'usd',
        ]);
        $user = Auth::user();
        $price = $request['price'];
        $numberOfTickets = $request['number_of_tickets'] ?? 1;
        $tripId = $request['trip_id'];
        $passengers = $request['passengers'];
        DB::beginTransaction();
        try {

            Stripe::setApiKey(env('STRIPE_SECRET'));

            $charge = Charge::create([
                'amount' => Booking::find($$request->booking_id)->price * 100,
                'currency' => 'usd',
                'description' => 'حجز رحلة',
                'source' => $request->stripeToken,
            ]);

            $paymentIntent = PaymentIntent::create([
                'amount' => intval($price * 100), //cents
                'currency' => 'usd',
                'payment_method' => $request['payment_method'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'user_id' => $user->id,
                    'trip_id' => $tripId,
                ],
            ]);
            if($paymentIntent->status != 'succeeded'){
                DB::rollBack();
                return [
                    'message' => 'Payment not successful',
                    'code' => 402,
                ];
            }
            $booking = Booking::create([
                'user_id' => $user->id,
                'trip_id' => $tripId,
                'number_of_tickets' => $numberOfTickets,
                'price' => $price,
                'payment_method' => 'stripe',
                'qr_code' => null,
            ]);

            foreach($passengers as $passengerData){
                $booking->passengers()->create($passengerData);
            }
            $qrContent = json_encode([
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'trip_id' => $tripId,
            ]);

            $result = Builder::create()
                ->data($qrContent)
                ->size(300)
                ->build();
            $path = 'public/qrcodes/booking_'.$booking->id.'.png';
            Storage::put($path, $result->getString());
            $booking->qr_code = $path;
            $booking->save();
            DB::commit();
            return [
                'message' => 'Booking and payment successful',
                'code' => 201,
                'booking' => $booking,
                'qr_code_url' => asset('storage/qrcodes/booking_'.$booking->id.'.png'),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'message' => 'Error: ' . $e->getMessage(),
                'code' => 500,
            ];
        }
    }


    public function cancelReservation($id)
    {
        $type=request()->query('type');
        $type+="_id";
        $booking = Booking::find($id);
        if (!$booking||$booking->$type==null) {
            return ['message' => 'Booking not found.', 'code' => 404];
        }
            Refund::create([
            'payment_intent' => $request->payment_intent_id,
        ]);
        $booking->delete();
        return ['message' => 'Reservation cancelled.', 'code' => 200];
    }

    public function myReserved()
    {
        $user = Auth::user();
        $trips = $user->bookings()->whereNotNull('trip_id')->with('trip')->get()->pluck('trip');
        if ($trips->isNotEmpty()) {
            return [
                'trips'   => ReservationTripResource::collection($trips),
                'message' => 'All reserved trips retrieved.',
                'code'    => 200,
            ];
        }
        return [
            'trips'   => null,
            'message' => 'No trips reserved.',
            'code'    => 404,
        ];
    }

    public function reserve($request)
    {
        $user = Auth::user();
        $type=$request['type'];
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
            }
        }
        else if($type=='flight')
        {
            $flight=Flight::find($request['id']);
            if(($flight->departure_date." ".$flight->departure_time)<=now())
                return['message'=>"the flight has ended",'code'=>400];
            if($request['number_of_tickets']!=count($request['passengers']))
                return['message'=>"the number of tickets must be equal to size of passengers array",'code'=>400];
            $remainingTickets=$flight->number_of_tickets-$flight->reserved_tickets;
            if(($request['number_of_adults']+$request['number_of_children']+$request['number_of_infants'])>$remainingTickets)
                return['message'=>"the number of tickets not available",'code'=>400];
            $adults=0;
            $children=0;
            $infants=0;
            foreach($request['passengers'] as $passenger){
                $age=$passenger['birth_date']->diffInYears(now());
                if($age<2)
                    $infants++;
                else if($age>2&&$age<18)
                    $children++;
                else
                    $adults++;
            }
            if($adults!=$request['adults']||$children!=$request['children']||$infants!=$request['infants'])
                return['message'=>"the number of tickets not available",'code'=>400];
        }
        $booking=$user->bookings()->create([
            $type.'_id'=>$request['id'],
            ...$request
        ]);
        foreach($request['passengers'] as $passenger)
            $booking->passengers()->create($passenger);
        return [
            'message' => 'please pay to confirm bookings',
            'code' => 201,
            'booking' => $booking??null
        ];
    }
}
