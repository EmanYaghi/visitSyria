<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

             $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('booking_id');
            $table->string('payment_intent_id')->nullable();
            $table->integer('amount');            // amount in cents
            $table->string('status')->default('pending'); // pending, succeeded, failed, refunded
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
