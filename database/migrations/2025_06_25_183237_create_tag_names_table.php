<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagNamesTable extends Migration
{
    public function up()
    {
        Schema::create('tag_names', function (Blueprint $table) {
            $table->id();
            $table->string('body');
            $table->enum('follow_to', ['post', 'place', 'trip', 'article']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tag_names');
    }
}
