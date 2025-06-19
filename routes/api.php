<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WeatherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function () {

    Route::post('register', [AuthController::class,'register']);
    Route::post('verifyEmail', [AuthController::class,'verifyEmail']);
    Route::post('login', [AuthController::class,'login']);
    Route::post('logout', [AuthController::class,'logout'])->middleware('auth:api');
    Route::post('changePassword',[AuthController::class,'changePassword'])->middleware('auth:api');
    Route::post('forgetPassword',[AuthController::class,'forgetPassword']);
    Route::post('resetPassword',[AuthController::class,'resetPassword']);
    Route::post('profile', [AuthController::class,'profile'])->middleware('auth:api');
    Route::post('resendVerificationCode',[AuthController::class,'resendVerificationCode']);
    Route::post('verifyCode', [AuthController::class,'verifyCode']);
    Route::post('deleteAccount', [AuthController::class,'deleteAccount'])->middleware('auth:api');
    Route::post('loginWithGoogle', [AuthController::class, 'loginWithGoogle']);

});

Route::get('weather', [WeatherController::class, 'getForecast']);

?>