<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\StripePaymentService;

class WebhookController extends Controller
{
    protected $stripe;
    public function __construct(StripePaymentService $stripe)
    {
        $this->stripe = $stripe;
    }

    // Stripe sends events here; we update our records accordingly
    public function __invoke(Request $request)
    {
        $this->stripe->handleWebhook($request->all());
        return response('OK', 200);
    }
}
