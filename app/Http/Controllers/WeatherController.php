<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function getForecast()
    {
        $apiKey = env('WEATHER_API_KEY');
        $days = 7;

        $cities = [
            'Damascus', 'Aleppo', 'Homs', 'Hama', 'Latakia', 'Tartus',
            'Daraa', 'As-Suwayda', 'Al-Hasakah', 'Deir ez-Zor',
            'Raqqa', 'Idlib', 'Quneitra', 'Rif Dimashq'
        ];

        $allForecasts = [];

        foreach ($cities as $city) {
            $response = Http::get("http://api.weatherapi.com/v1/forecast.json", [
                'key' => $apiKey,
                'q' => $city,
                'days' => $days,
                'aqi' => 'no',
                'alerts' => 'no'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $location = $data['location']['name'] ?? $city;

                $forecast = [];
                foreach ($data['forecast']['forecastday'] as $day) {
                    $forecast[] = [
                        'date' => $day['date'],
                        'temp_c' => $day['day']['maxtemp_c'],
                        'condition' => $day['day']['condition']['text'],
                    ];
                }

                $allForecasts[] = [
                    'location' => $location,
                    'forecast' => $forecast
                ];
            }
        }

        return response()->json($allForecasts);
    }
}
