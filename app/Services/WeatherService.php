<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Support\WeatherConditionClassifier;
use Illuminate\Http\Request;

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

    public function getForecastByCity(string $city): array
    {
        $response = Http::get("http://api.weatherapi.com/v1/forecast.json", [
            'key'    => $this->apiKey,
            'q'      => $city,
            'days'   => $this->days,
            'aqi'    => 'no',
            'alerts' => 'no',
        ]);

        if (! $response->successful()) {
            return ['error' => 'Failed to fetch weather data'];
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

        return [
            'location' => $location,
            'forecast' => $forecast,
        ];
    }
    public function handleForecastRequest(Request $request): array
    {
        $city = $request->input('city');
        
        if (!$city) {
            return ['error' => 'City is required'];
        }
        return $this->getForecastByCity($city);
    }

    public function getTodayWeather(): array
{
    $todayWeather = [];

    foreach ($this->cities as $city) {
        $response = Http::get("http://api.weatherapi.com/v1/forecast.json", [
            'key'    => $this->apiKey,
            'q'      => $city,
            'days'   => 1,
            'aqi'    => 'no',
            'alerts' => 'no',
        ]);

        if (! $response->successful()) {
            continue;
        }

        $data = $response->json();
        $day = $data['forecast']['forecastday'][0] ?? null;

        if ($day) {
            $text = $day['day']['condition']['text'];
            $todayWeather[] = [
                'location'       => $data['location']['name'] ?? $city,
                'date'           => $day['date'],
                'day_name'       => date('l', strtotime($day['date'])),
                'temp_c'         => $day['day']['maxtemp_c'],
                'condition_type' => WeatherConditionClassifier::classify($text),
            ];
        }
    }

    return $todayWeather;
}


}
