<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('post_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->foreignId('trip_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->foreignId('place_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            // $table->foreignId('company_id')
            //       ->nullable()
            //       ->constrained()
            //       ->nullOnDelete();
            $table->foreignId('support_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('comments');
    }
}
