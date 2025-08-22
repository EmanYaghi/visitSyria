<?php

use App\Http\Controllers\SettingController;
use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\CityController;
    use App\Http\Controllers\WeatherController;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\BookingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EventController;
    use App\Http\Controllers\FeedbackController;
    use App\Http\Controllers\FlightController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\PlaceController;
    use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TripController;
    use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TripPlannerController;
use App\Http\Controllers\UserController;
use Stripe\Stripe;
use Stripe\Token;

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

        Route::post('registerCompanyBySuperAdmin', [AuthController::class,'registerCompanyBySuperAdmin'])->middleware('auth:api');

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


        Route::post('articles', [ArticleController::class, 'store']);
        Route::post('articles/{article}', [ArticleController::class, 'update']);
        Route::delete('articles/{article}', [ArticleController::class, 'destroy']);

        Route::post('settings', [SettingController::class, 'store']);
        Route::post('settings/{setting}', [SettingController::class, 'update']);
        Route::delete('settings/{setting}', [SettingController::class, 'destroy']);



        Route::post('trips', [TripController::class,'store']);
        Route::delete('trips/{id}', [TripController::class,'destroy']);
        Route::post('trip/update/{id}', [TripController::class,'update']);
        Route::post('trips/cancel/{id}',[TripController::class,'cancel']);
        Route::get('trips/lastTrip', [TripController::class,'lastTrip']);

        Route::post('reserve', [BookingController::class, 'reserve']);

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


        Route::post('posts', [PostController::class, 'store']);
        Route::post('posts/status', [PostController::class, 'updateStatus']);

        Route::post('/posts/{post}/like', [FeedbackController::class, 'toggleLike']);

        Route::post('bookFlight', [FlightController::class, 'bookFlight']);

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
    Route::get('places/tourist/byCity', [PlaceController::class, 'getTouristPlacesByCity']);

    Route::get('places/tourist/{classification}', [PlaceController::class, 'getTouristPlacesByClassification']);
    Route::get('places/restaurants/byCity', [PlaceController::class, 'getRestaurantsByCity']);
    Route::get('places/hotels/byCity', [PlaceController::class, 'getHotelsByCity']);
    Route::get('places/tourist/{classification}/city/{cityName}', [PlaceController::class, 'getTouristPlacesByClassificationAndCity']);

    Route::get('places/top-rated-tourist', [PlaceController::class, 'getTopRatedTouristPlaces']);
    Route::get('places/top-restaurants', [PlaceController::class, 'getTopRatedRestaurants']);
    Route::get('places/top-hotels', [PlaceController::class, 'getTopRatedHotels']);
    Route::get('places', [PlaceController::class,'index']);
    Route::get('places/{id}/similar', [PlaceController::class, 'similarPlaces']);
    Route::get('places/{id}', [PlaceController::class,'show']);

    Route::get('events', [EventController::class,'index']);
    Route::get('events/{id}', [EventController::class,'show']);

    Route::get('admin/events', [EventController::class, 'adminIndex']);
    Route::post('admin/events/{id}/cancel', [EventController::class, 'cancel']);


    Route::get('cities', [CityController::class, 'index']);
    Route::get('cities/{id}', [CityController::class, 'show']);

    Route::get('flights/search', [FlightController::class, 'search']);
    Route::get('/locations/search', [FlightController::class, 'searchLocation']);


    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('articles/{article}', [ArticleController::class, 'show']);
    Route::get('articles/{article}/similar', [ArticleController::class, 'similar']);
    Route::get('articles/by-tag/{tag}', [ArticleController::class, 'getByTag']);

    Route::get('settings', [SettingController::class, 'index']);
    Route::get('settings/{setting}', [SettingController::class, 'show']);
    Route::get('settings/type/{type}', [SettingController::class, 'getByType']);
    Route::get('settings/category/{category}', [SettingController::class, 'getByCategory']);

    Route::get('posts/by-status', [PostController::class, 'byStatus'])->middleware('auth:api');
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{post}', [PostController::class, 'show']);
    Route::get('users/top-active', [PostController::class, 'topActiveUsers']);

    Route::get('myPosts', [PostController::class, 'myPosts'])->middleware('auth:api');

    Route::get('feedback/{id}', [FeedbackController::class, 'getFeedback']);


    Route::group(['middleware' => ['jwt.auth']], function () {
        Route::post('/trips/{trip}/reserve', [BookingController::class, 'reserve']);
        Route::post('/bookings/{booking}/pay', [BookingController::class, 'pay']);
        Route::delete('/bookings/{booking}/cancel', [BookingController::class, 'cancelReservation']);

        Route::post('bookFlight', [BookingController::class, 'bookFlight']);

        Route::get('myBookings', [BookingController::class, 'myBookings']);
        Route::get('persons/book/{id}', [BookingController::class, 'person']);

        Route::get('allUser',[UserController::class,'allUser']);
        Route::get('mostActiveUsers',[UserController::class,'mostActiveUsers']);
        Route::post('changeUserStatus',[UserController::class,'changeUserStatus']);
        Route::get('userActivities/{id}',[UserController::class,'userActivities']);
            Route::get('users/{id}', [UserController::class, 'getUser']);

    });

    Route::middleware('auth:api')->group(function () {
        Route::post('stripe/pay',           [PaymentController::class, 'pay']);
        Route::post('stripe/refund/{id}',   [PaymentController::class, 'refund']);
    });
    Route::post('/stripe/webhook', [WebhookController::class, 'handle']);


    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('/supports', [SupportController::class, 'store']);
        Route::get('/supports', [SupportController::class, 'index']);
    });
    Route::get('supports/monthly-ratings', [SupportController::class, 'monthlyRatings']);
    Route::get('companies',[CompanyController::class,'index']);
    Route::get('topCompanies',[CompanyController::class,'topCompanies'])->middleware('auth:api');
    Route::get('getCompaniesOnHold',[CompanyController::class,'getCompaniesOnHold'])->middleware('auth:api');
    Route::post('changeCompanyStatus',[CompanyController::class,'changeCompanyStatus'])->middleware('auth:api');
    Route::get('search',[FeedbackController::class,'search']);

Route::group(['middleware' => ['auth:api']], function () {
    Route::post('/itinerary', [TripPlannerController::class, 'generateTrip']);
    Route::get('/itineraries', [ItineraryController::class, 'index']);
    Route::get('/itineraries/{itinerary}', [ItineraryController::class, 'show']);
});
?>
