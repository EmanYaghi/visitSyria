<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;

class WeatherController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function getForecast(): JsonResponse
    {
        $forecasts = $this->weatherService->getForecasts();
        return response()->json($forecasts);
    }
}
