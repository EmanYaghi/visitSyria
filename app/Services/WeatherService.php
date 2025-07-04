<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Support\WeatherConditionClassifier;

class WeatherService
{
    protected string $apiKey;
    protected int $days = 7;

    protected array $cities = [
        'Damascus', 'Aleppo', 'Homs', 'Hama', 'Latakia', 'Tartus',
        'Daraa', 'As-Suwayda', 'Al-Hasakah', 'Deir ez-Zor',
        'Raqqa', 'Idlib', 'Quneitra', 'Rif Dimashq'
    ];

    public function __construct()
    {
        $this->apiKey = env('WEATHER_API_KEY');
    }

    public function getForecasts(): array
    {
        $allForecasts = [];

        foreach ($this->cities as $city) {
            $response = Http::get("http://api.weatherapi.com/v1/forecast.json", [
                'key'    => $this->apiKey,
                'q'      => $city,
                'days'   => $this->days,
                'aqi'    => 'no',
                'alerts' => 'no',
            ]);

            if (! $response->successful()) {
                continue;
            }

            $data = $response->json();
            $location = $data['location']['name'] ?? $city;

            $forecast = [];
            foreach ($data['forecast']['forecastday'] as $day) {
                $text = $day['day']['condition']['text'];
                $forecast[] = [
                    'date'          => $day['date'],
                    'day_name'      => date('l', strtotime($day['date'])),
                    'temp_c'        => $day['day']['maxtemp_c'],
                    'condition_type'=> WeatherConditionClassifier::classify($text),
                ];
            }

            $allForecasts[] = [
                'location' => $location,
                'forecast' => $forecast,
            ];
        }

        return $allForecasts;
    }
}
