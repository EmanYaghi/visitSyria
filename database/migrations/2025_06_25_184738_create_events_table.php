<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
public function up()
{
    Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
        $table->string('name');
        $table->text('description');
        $table->decimal('longitude', 10, 7);
        $table->decimal('latitude', 10, 7);
        $table->date('date');
        $table->integer('duration');
        $table->string('place');
        $table->integer('tickets');
        $table->decimal('price', 8, 2);
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('events');
}

};
