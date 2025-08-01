<?php

    use App\Http\Controllers\CityController;
    use App\Http\Controllers\WeatherController;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\BookingController;
    use App\Http\Controllers\EventController;
    use App\Http\Controllers\FeedbackController;
    use App\Http\Controllers\PlaceController;
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
        Route::post('setAdminProfile', [AuthController::class,'setAdminProfile'])->middleware('auth:api');
        Route::post('updateAdminProfile', [AuthController::class,'updateAdminProfile'])->middleware('auth:api');
        Route::get('adminProfile', [AuthController::class,'getAdminProfile'])->middleware('auth:api');
    });

    Route::get('weather', [WeatherController::class, 'getForecast']);
    Route::get('/weather/today', [WeatherController::class, 'todayWeather']);

    Route::middleware('auth:api')->group(function () {

        Route::post('events', [EventController::class,'store']);
        Route::post('updateEvent/{id}', [EventController::class,'update']);
        Route::delete('events/{id}', [EventController::class,'destroy']);

        Route::post('places', [PlaceController::class,'store']);
        Route::post('updatePlace/{id}', [PlaceController::class,'update']);
        Route::delete('places/{id}', [PlaceController::class,'destroy']);

        Route::post('trips', [TripController::class,'store']);
        Route::delete('trips/{id}', [TripController::class,'destroy']);
        Route::post('trip/update/{id}', [TripController::class,'update']);
        Route::post('trips/reserve', [TripController::class, 'reserve']);

        Route::post('bookings/{booking}/pay', [BookingController::class, 'pay']);
        Route::delete('bookings/{booking}/cancel', [BookingController::class, 'cancelReservation']);
        Route::get('myReserved', [BookingController::class, 'myReservedTrips']);

        Route::post('saves/{id}',[FeedbackController::class,'setSave']);
        Route::post('comments/{id}',[FeedbackController::class,'setComment']);
        Route::post('ratings/{id}',[FeedbackController::class,'setRating']);
        Route::delete('saves/{id}',[FeedbackController::class,'deleteSave']);
        Route::delete('comments/{id}',[FeedbackController::class,'deleteComment']);
        Route::delete('ratings/{id}',[FeedbackController::class,'deleteRating']);
        Route::get('saves',[FeedbackController::class,'getSaves']);

    });
    Route::get('trips', [TripController::class,'index']);
    Route::get('trips/{id}', [TripController::class,'show']);
    Route::get('trip/company/{id}', [TripController::class,'companyTrips']);
    Route::get('trip/offers', [TripController::class,'offers']);
    Route::get('trips/similar/{id}',[TripController::class,'similarTrips']);

    

    Route::get('places/restaurants', [PlaceController::class, 'getRestaurants']);
    Route::get('places/hotels', [PlaceController::class, 'getHotels']);
    Route::get('places/tourist', [PlaceController::class, 'getTouristPlaces']);

    Route::get('places/city/{cityName}', [PlaceController::class, 'getPlacesByCity']);
    Route::get('city/{cityName}/places', [PlaceController::class, 'cityPlaces']);

    Route::get('places/top-rated-tourist', [PlaceController::class, 'getTopRatedTouristPlaces']);
    Route::get('places/tourist/{classification}', [PlaceController::class, 'getTouristPlacesByClassification']);
    Route::get('places/restaurants/byCity', [PlaceController::class, 'getRestaurantsByCity']);
    Route::get('places/hotels/byCity', [PlaceController::class, 'getHotelsByCity']);
    Route::get('places/tourist/{classification}/city/{cityName}', [PlaceController::class, 'getTouristPlacesByClassificationAndCity']);


    Route::get('places', [PlaceController::class,'index']);
    Route::get('places/{id}', [PlaceController::class,'show']);


    
    Route::get('events', [EventController::class,'index']);
    Route::get('events/{id}', [EventController::class,'show']);

    Route::get('cities', [CityController::class, 'index']);
    Route::get('cities/{id}', [CityController::class, 'show']);

    Route::group(['middleware' => ['jwt.auth']], function () {
        Route::post('/trips/{trip}/reserve', [BookingController::class, 'reserve']);
        Route::post('/bookings/{booking}/pay', [BookingController::class, 'pay']);
        Route::delete('/bookings/{booking}/cancel', [BookingController::class, 'cancelReservation']);

        Route::get('trips/myReserved', [BookingController::class, 'myReservedTrips']);
    });
?>


