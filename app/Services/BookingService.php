<?php

namespace App\Services;

use App\Http\Resources\ReservationTripResource;
use App\Models\Booking;
use App\Models\Passenger;
use App\Models\User;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function reserve(array $data)
    {
        $user = Auth::user();
        $price = $data['price'];
        $numberOfTickets = $data['number_of_tickets'] ?? 1;
        $tripId = $data['trip_id'];
        $passengers = $data['passengers'];
        // ابدأ معاملة قاعدة البيانات لضمان الذمة
        DB::beginTransaction();
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $paymentIntent = PaymentIntent::create([
                'amount' => intval($price * 100), // بالمئة (cents)
                'currency' => 'usd',
                'payment_method' => $data['payment_method'], // ID لطريقة الدفع (مثل Stripe card token)
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
            \Storage::put($path, $result->getString());
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
        $booking = Booking::find($id);

        if (!$booking) {
            return ['message' => 'Booking not found.', 'code' => 404];
        }

        $booking->delete();

        return ['message' => 'Reservation cancelled.', 'code' => 200];
    }

    public function myReservedTrips()
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
}
