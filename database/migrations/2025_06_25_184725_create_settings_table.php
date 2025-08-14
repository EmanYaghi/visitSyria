<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    public function up()
    {
       Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->enum('type', [
        'privacy_policy',
        'common_question',
        'about_app'
    ]);
    $table->index('type');
    $table->enum('category', ['app', 'admin'])->default('app');
    $table->index('category');
    $table->string('title');
    $table->longText('description')->nullable();
    $table->timestamps();
});
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
