<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\StripeService;

class StripeController extends Controller
{
    protected $stripe;

    public function __construct(StripeService $stripe)
    {
        $this->stripe = $stripe;
    }

    public function setupIntent(Request $request)
    {
        $user = auth()->user();
        $clientSecret = $this->stripe->createSetupIntent($user);

        return response()->json([
            'client_secret' => $clientSecret
        ]);
    }

    public function saveCard(Request $request)
    {
        $user = auth()->user();
        $message = $this->stripe->saveCard($user);

        return response()->json([
            'message' => $message
        ]);
    }

    public function pay(Request $request)
    {
        $user = auth()->user();
        $amount = $request->amount;

        $paymentIntent = $this->stripe->payWithSavedCard($user, $amount);

        return response()->json($paymentIntent);
    }
}
