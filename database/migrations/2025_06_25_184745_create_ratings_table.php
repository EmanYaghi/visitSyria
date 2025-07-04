<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingsTable extends Migration
{
    public function up()
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('place_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->foreignId('trip_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->unsignedTinyInteger('rating_value')
                  ->default(0);
            $table->enum('classification', ['positive', 'negative'])
                  ->default('positive');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ratings');
    }
}
