<?php

namespace App\Services;

use App\Models\User;
use App\Models\CreditCard;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\SetupIntent;
use Stripe\PaymentMethod;
use Stripe\PaymentIntent;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function createCustomer(User $user): string
    {
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
        ]);

        $user->stripe_customer_id = $customer->id;
        $user->save();

        return $customer->id;
    }

    public function createSetupIntent(User $user): string
    {
        $customerId = $user->stripe_customer_id ?? $this->createCustomer($user);

        $setupIntent = SetupIntent::create([
            'customer' => $customerId,
        ]);

        return $setupIntent->client_secret;
    }

    public function saveCard(User $user)
    {
        $methods = PaymentMethod::all([
            'customer' => $user->stripe_customer_id,
            'type' => 'card',
        ]);

        $paymentMethod = $methods->data[0] ?? null;

        if (!$paymentMethod) {
            throw new \Exception("لم يتم العثور على بطاقة.");
        }

        // احذف البطاقات السابقة إذا أردت واحدة فقط
        CreditCard::where('user_id', $user->id)->delete();

        CreditCard::create([
            'user_id' => $user->id,
            'card_holder' => $paymentMethod->billing_details->name,
            'card_number' => '**** **** **** ' . $paymentMethod->card->last4,
            'expiry_date' => $paymentMethod->card->exp_month . '/' . $paymentMethod->card->exp_year,
            'stripe_payment_method_id' => $paymentMethod->id,
        ]);

        return "تم حفظ البطاقة بنجاح.";
    }

    public function payWithSavedCard(User $user, $amount)
    {
        $card = $user->creditCard;

        if (!$card) {
            throw new \Exception("لا توجد بطاقة محفوظة.");
        }

        $paymentIntent = PaymentIntent::create([
            'amount' => $amount * 100, // المبلغ بالسنت
            'currency' => 'usd',
            'customer' => $user->stripe_customer_id,
            'payment_method' => $card->stripe_payment_method_id,
            'off_session' => true,
            'confirm' => true,
        ]);

        return $paymentIntent;
    }
}
