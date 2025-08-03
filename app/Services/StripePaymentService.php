<?php
namespace App\Services;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\SetupIntent;
use Stripe\PaymentIntent;
use Stripe\Refund;
use App\Models\User;
use App\Models\Payment;
use App\Models\CreditCard;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // Make sure each user has a Stripe customer record
    public function createCustomer(User $user): string
    {
        if (!$user->stripe_customer_id) {
            $customer = Customer::create([
                'email' => $user->email,
                'name'  => $user->name,
            ]);
            $user->update(['stripe_customer_id' => $customer->id]);
        }
        return $user->stripe_customer_id;
    }

    // Get a client secret so the client can add a new card
    public function generateSetupIntent(User $user): string
    {
        $customerId = $this->createCustomer($user);
        $intent = SetupIntent::create(['customer' => $customerId]);
        return $intent->client_secret;
    }

    // Save a new card in our database
    public function storePaymentMethod(User $user, string $paymentMethodId, bool $makeDefault = false): CreditCard
    {
        $pm = \Stripe\PaymentMethod::retrieve($paymentMethodId);


        // Save card details (non-sensitive) locally
        $card = CreditCard::create([
            'user_id'                  => $user->id,
            'stripe_payment_method_id' => $pm->id,
            'card_holder'              => $pm->billing_details->name,
            'brand'                    => $pm->card->brand,
            'last4'                    => $pm->card->last4,
            'exp_month'                => $pm->card->exp_month,
            'exp_year'                 => $pm->card->exp_year,
            'is_default'               => false,
        ]);

        // If requested, set this card as default
        if ($makeDefault) {
            $this->setDefaultCard($user, $card->id);
        }

        return $card;
    }

    // Only one default card per user: clear the old one and set the new one
    public function setDefaultCard(User $user, int $cardId): CreditCard
    {
        CreditCard::where('user_id', $user->id)
                  ->where('is_default', true)
                  ->update(['is_default' => false]);

        $card = CreditCard::findOrFail($cardId);
        $card->update(['is_default' => true]);

        return $card;
    }

    // Start a payment for any reservation (tour, event, flight)
    public function createPaymentIntent(
        User $user,
        int $amountCents,
        string $type,
        int $reservationId,
        ?string $paymentMethodId = null
    ): string {
        $customerId = $this->createCustomer($user);
        $data = [
            'amount'    => $amountCents,
            'currency'  => 'usd',
            'customer'  => $customerId,
            'metadata'  => [
                'reservation_type' => $type,
                'reservation_id'   => $reservationId,
                'user_id'          => $user->id,
            ],
        ];

        // If a saved card is provided, charge it immediately
        if ($paymentMethodId) {
            $data['payment_method'] = $paymentMethodId;
            $data['off_session']    = true;
            $data['confirm']        = true;
        }
        // Otherwise, let the client confirm the payment and save card for future use
        else {
            $data['payment_method_types'] = ['card'];
            $data['setup_future_usage']   = 'off_session';
        }

        $intent = PaymentIntent::create($data);

        // Record this intent in our database
        Payment::create([
            'user_id'          => $user->id,
            'payment_intent_id'=> $intent->id,
            'amount'           => $amountCents,
            'status'           => $intent->status,
            "{$type}_reservation_id" => $reservationId,
        ]);

        return $intent->client_secret;
    }

    // Handle Stripe webhooks for payment success/failure
    public function handleWebhook(array $payload): void
    {
        $type   = $payload['type'];
        $intent = $payload['data']['object'];
        $payment = Payment::where('payment_intent_id', $intent['id'])->first();
        if (! $payment) return;

        if ($type === 'payment_intent.succeeded') {
            $payment->update(['status' => 'succeeded']);
        }
        elseif ($type === 'payment_intent.payment_failed') {
            $error = $intent['last_payment_error'] ?? [];
            $payment->update([
                'status'         => 'failed',
                'metadata->failure_code'    => $error['code'] ?? null,
                'metadata->failure_message' => $error['message'] ?? 'Payment failed',
            ]);
        }
    }

    // Issue a refund for a successful payment
    public function refund(Payment $payment): Payment
    {
        if ($payment->status !== 'succeeded') {
            throw new \Exception('Can only refund completed payments.');
        }
        Refund::create(['payment_intent' => $payment->payment_intent_id]);
        $payment->update(['status' => 'refunded']);

        return $payment;
    }
}
