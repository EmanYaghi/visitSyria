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
            $table->string('stripe_payment_method_id')->nullable();
            $table->string('card_holder');
            $table->string('card_number');
            $table->string('cvc');
            $table->date('expiry_date');
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('credit_cards');
    }
}
