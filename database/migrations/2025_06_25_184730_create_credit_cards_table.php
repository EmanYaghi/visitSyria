<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditCardsTable extends Migration
{
    public function up()
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();

             $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_payment_method_id'); // Stripe's token
            $table->string('card_holder')->nullable();     // cardholder name
            $table->string('brand');                      // visa, mastercard, etc.
            $table->string('last4');                      // last 4 digits
            $table->integer('exp_month');                 // expiration month
            $table->integer('exp_year');                  // expiration year
            $table->boolean('is_default')->default(false); // user's preferred card

            // Only one card can be default per user
            $table->unique(['user_id','is_default'], 'unique_default_card')
                  ->where('is_default', true);

            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            $table->dropUnique('unique_default_card');
        });
        Schema::dropIfExists('credit_cards');
    }
}
