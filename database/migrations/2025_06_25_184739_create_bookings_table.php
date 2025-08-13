<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('flight_id')->nullable()
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->foreignId('event_id')->nullable()
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->unsignedInteger('number_of_tickets')->default(1);
            $table->unsignedInteger('number_of_adults')->default(0);
            $table->unsignedInteger('number_of_children')->default(0);
            $table->unsignedInteger('number_of_infants')->default(0);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('stripe_payment_id')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('qr_code')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}
