<?php
use App\Models\City;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlacesTable extends Migration
{
public function up()
{
    Schema::create('places', function (Blueprint $table) {
        $table->id();
        $table->foreignId('city_id')
                  ->constrained()
                  ->cascadeOnDelete();
        $table->enum('type', ['hotel', 'restaurant', 'tourist'])->default('tourist');
        $table->string('name');
        $table->text('description')->nullable();
        $table->unsignedSmallInteger('number_of_branches')->default(1);
        $table->string('phone')->nullable();
        $table->string('country_code', 5)->nullable();
        $table->string('place')->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('rating', 3, 2)->default(0);
        $table->string('classification')->nullable();
        $table->timestamps();
    });
}



    public function down()
    {
        Schema::dropIfExists('places');
    }
}
