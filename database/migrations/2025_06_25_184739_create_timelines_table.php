<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimelinesTable extends Migration
{
    public function up()
    {
        Schema::create('timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('flight_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('day_number')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('timelines');
    }
}
