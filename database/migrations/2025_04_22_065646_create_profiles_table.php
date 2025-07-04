<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfilesTable extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('country');
            $table->string('phone')->nullable();
            $table->string('country_code');
            $table->string('lang')->default('en');
            $table->string('theme_mode')->default('light');
            $table->boolean('allow_notification')->default(true);
            $table->string('experience')->nullable();
            $table->integer('number_of_trips')->nullable()->default(0);
            $table->float('rating')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
