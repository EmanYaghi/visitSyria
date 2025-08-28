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
        $table->string('name');
        $table->text('description');
        $table->decimal('longitude', 10, 7);
        $table->decimal('latitude', 10, 7);
        $table->string('place');
        $table->date('date');
        $table->integer('duration_days')->nullable();
        $table->integer('duration_hours')->nullable();
        $table->integer('tickets')->default(0);
        $table->unsignedInteger('reserved_tickets')->default(0);
        $table->decimal('price', 8, 2);
        $table->enum('event_type', ['limited', 'unlimited'])->default('limited');
        $table->enum('price_type', ['free', 'paid'])->default('free');
        $table->boolean('pre_booking')->default(false);
        $table->enum('status', ['active', 'cancelled'])->default('active');
        $table->enum('status2',['لم تبدأ بعد','منتهية','جارية حاليا','تم الالغاء'])->default('لم تبدأ بعد');
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('events');
}

};
