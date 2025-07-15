<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TripController;

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
    Route::post('resendVerificationCode',[AuthController::class,'resendVerificationCode']);
    Route::post('verifyCode', [AuthController::class,'verifyCode']);
    Route::post('deleteAccount', [AuthController::class,'deleteAccount'])->middleware('auth:api');
    Route::post('loginWithGoogle', [AuthController::class, 'loginWithGoogle']);

    Route::post('setProfile', [AuthController::class,'setProfile'])->middleware('auth:api');
    Route::post('updateProfile', [AuthController::class,'updateProfile'])->middleware('auth:api');
    Route::get('profile', [AuthController::class,'getProfile'])->middleware('auth:api');
    Route::post('setPreference', [AuthController::class,'setPreference'])->middleware('auth:api');
    Route::post('updatePreference', [AuthController::class,'updatePreference'])->middleware('auth:api');
    Route::post('setAdminProfile', [AuthController::class,'setAdminProfile'])->middleware('auth:api');
    Route::post('updateAdminProfile', [AuthController::class,'updateAdminProfile'])->middleware('auth:api');
    Route::get('adminProfile', [AuthController::class,'getAdminProfile'])->middleware('auth:api');

});

    Route::get('weather', [WeatherController::class, 'getForecast']);
    Route::get('/weather/today', [WeatherController::class, 'todayWeather']);

    Route::middleware('auth:api')->group(function () {
        Route::resource('events', EventController::class);
        Route::post('updateEvent/{id}', [EventController::class,'updateُ']);

        Route::resource('trips', TripController::class);
        Route::post('trips/company/{id}', [TripController::class,'companyTrips']);
    });


    Route::get('cities', [CityController::class, 'index']);
    Route::get('cities/{id}', [CityController::class, 'show']);
?>


