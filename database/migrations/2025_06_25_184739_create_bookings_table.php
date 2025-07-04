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
            $table->foreignId('trip_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('flight_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->foreignId('event_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->unsignedInteger('number_of_tickets')->default(1);
            $table->unsignedInteger('number_of_adults')->default(1);
            $table->unsignedInteger('number_of_children')->default(0);
            $table->unsignedInteger('number_of_infants')->default(0);
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->decimal('price', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('qr_code')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}
