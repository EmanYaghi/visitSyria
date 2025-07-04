<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlightsTable extends Migration
{
    public function up()
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->enum('direction', ['back', 'go', 'back and go']);
            $table->string('airline');
            $table->string('type');
            $table->string('departure_airport');
            $table->string('destination_airport');
            $table->date('departure_date');
            $table->time('departure_time');
            $table->date('return_date')->nullable();
            $table->time('return_time')->nullable();
            $table->unsignedInteger('duration')->default(1); // in hours
            $table->unsignedInteger('number_of_stopovers')->default(0);
            $table->unsignedInteger('number_of_tickets')->default(1);
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('flights');
    }
}
