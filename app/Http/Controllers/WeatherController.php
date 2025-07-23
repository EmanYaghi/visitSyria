<?php
namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WeatherController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function getForecast(Request $request): JsonResponse
    {
        $forecast = $this->weatherService->handleForecastRequest($request);
        return response()->json($forecast);
    }

    public function todayWeather(WeatherService $weatherService)
    {
        $todayWeather = $weatherService->getTodayWeather();
        return response()->json($todayWeather);
    }

}
