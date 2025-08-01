<?php
namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Http\Requests\ReserveRequest;
use App\Http\Requests\PassengerRequest;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Throwable;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

     public function pay(Request $request)
    {
        return $this->stripeService->handlePayment($request);
    }

    public function cancel(Request $request)
    {
        return $this->stripeService->refund($request->payment_intent_id);
    }

    public function pay($bookingId,PaymentRequest $request)
    {
        if(request()->query(''))
            $data=$request->validated();
        else
            $data=[];
        return response()->json($this->bookingService->pay($bookingId,$data));
    }

    public function myReserved()
    {
        return response()->json($this->bookingService->myReserved());
    }

    public function cancelReservation($bookingId)
    {
        $data = $this->bookingService->cancelReservation($bookingId);
        return response()->json(['message' => $data['message']], $data['code']);
    }
}
