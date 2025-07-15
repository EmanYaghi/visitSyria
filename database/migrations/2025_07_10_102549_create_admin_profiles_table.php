<?php

use App\Models\City;
use App\Models\Profile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
             $table->string('name_of_company');
            $table->string('name_of_owner');
            $table->date('founing_date');
            $table->string('license_number');
            $table->string('phone');
            $table->enum('country_code',City::$country_code);
            $table->string('description');
            $table->string('location');
            $table->string('latitude');
            $table->string('longitude');
            $table->integer('number_of_trips')->default(0);
            $table->float('rating')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_profiles');
    }
};
