<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Services\StripePaymentService;
use App\Models\CreditCard;
use App\Models\Payment;

class PaymentController extends Controller
{
    protected $stripe;
    public function __construct(StripePaymentService $stripe)
    {
        $this->stripe = $stripe;
    }

    // Provide a SetupIntent to the client for adding a new card
    public function setupIntent(Request $request)
    {
        $secret = $this->stripe->generateSetupIntent($request->user());
        return response()->json(['client_secret' => $secret]);
    }

    // Save the client's new card and optionally make it default
    public function storeCard(Request $request)
    {
        $request->validate(['payment_method_id' => 'required']);
        $card = $this->stripe->storePaymentMethod(
            $request->user(),
            $request->payment_method_id,
            true // mark new card as default
        );
        return response()->json($card);
    }

    // Charge the user for a tour, event, or flight
    public function pay(Request $request)
    {
        $request->validate([
            'type'           => 'required|in:tour,event,flight',
            'reservation_id' => 'required|integer',
            'amount'         => 'required|integer',
            'payment_method_id' => 'sometimes|string'
        ]);
        $secret = $this->stripe->createPaymentIntent(
            $request->user(),
            $request->amount,
            $request->type,
            $request->reservation_id,
            $request->payment_method_id ?? null
        );
        return response()->json(['client_secret' => $secret]);
    }

    // Refund a payment by its ID
    public function refund(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $this->stripe->refund($payment);
        return response()->json(['message' => 'Refund processed']);
    }
}
