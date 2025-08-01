<?php

use App\Models\Trip;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripsTable extends Migration
{
    public function up()
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('season',['الصيف','الخريف','الشتاء','الربيع'])->nullable();
            $table->date('start_date');
            $table->integer('days')->default(0);
            $table->integer('hours')->default(0);
            $table->string('duration');
            $table->json('improvements')->nullable();
            $table->unsignedInteger('tickets')->default(1);
            $table->unsignedInteger('reserved_tickets')->default(0);
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('new_price', 10, 2)->nullable();
            $table->enum('status',Trip::$status)->default('لم تبدأ بعد');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trips');
    }
}
