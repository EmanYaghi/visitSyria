<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmartAssistantAnswersTable extends Migration
{
    public function up()
    {
        Schema::create('smart_assistant_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->json('type_of_trips')->nullable();
            $table->json('duration')->nullable();
            $table->json('average_activity')->nullable();
            $table->json('travel_with')->nullable();
            $table->json('sleeping_in_hotel')->nullable();
            $table->json('type_of_places')->nullable();
            $table->json('cities')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('smart_assistant_answers');
    }
}
