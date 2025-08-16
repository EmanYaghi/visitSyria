<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Webhook;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        switch ($event->type) {
            case 'charge.succeeded':
                $charge = $event->data->object;
                $this->handleChargeSucceeded($charge);
                break;

            case 'charge.refunded':
                $charge = $event->data->object;
                $this->handleChargeRefunded($charge);
                break;

            default:
                Log::info('Unhandled event type: ' . $event->type);
        }

        return response('Webhook handled', 200);
    }

    private function handleChargeSucceeded($charge)
    {
        $booking = Booking::where('stripe_payment_id', $charge->id)->first();
        if ($booking) {
            $booking->is_paid = true;
            $booking->payment_status = $charge->status;
            $booking->save();
            Log::info("Booking #{$booking->id} marked as paid.");
        }
    }

    private function handleChargeRefunded($charge)
    {
        $booking = Booking::where('stripe_payment_id', $charge->id)->first();
        if ($booking) {
            $booking->is_paid = false;
            $booking->payment_status = 'refunded';
            $booking->save();
            Log::info("Booking #{$booking->id} marked as refunded.");
        }
    }

}
