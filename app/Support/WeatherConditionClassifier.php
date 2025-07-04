<?php
namespace App\Support;
class WeatherConditionClassifier
{
    protected static array $map = [
        'sun' => [
            'Sunny', 'Clear',
        ],
        'tornado' => [
            'Blowing snow', 'Blizzard', 'Mist', 'Fog', 'Freezing fog',
        ],
        'thunder' => [
            'Patchy freezing drizzle possible',
            'Thundery outbreaks possible',
            'Patchy light rain with thunder',
            'Moderate or heavy rain with thunder',
            'Patchy light snow with thunder',
            'Moderate or heavy snow with thunder',
        ],
        'rain' => [
            'Patchy rain possible', 'Patchy light drizzle', 'Light drizzle',
            'Patchy light rain', 'Light rain', 'Moderate rain at times',
            'Moderate rain', 'Heavy rain at times', 'Heavy rain',
            'Light rain shower', 'Moderate or heavy rain shower',
            'Torrential rain shower',
        ],
        'snow' => [
            'Patchy snow possible', 'Patchy light snow', 'Light snow',
            'Patchy moderate snow', 'Moderate snow', 'Patchy heavy snow',
            'Heavy snow', 'Light snow showers', 'Moderate or heavy snow showers',
        ],
        'sleet' => [
            'Patchy sleet possible', 'Light sleet', 'Moderate or heavy sleet',
            'Light sleet showers', 'Moderate or heavy sleet showers',
            'Ice pellets', 'Light showers of ice pellets',
            'Moderate or heavy showers of ice pellets',
            'Freezing drizzle', 'Heavy freezing drizzle',
            'Light freezing rain', 'Moderate or heavy freezing rain',
        ],
        'cloud' => [
            'Partly cloudy', 'Cloudy', 'Overcast',
        ],
    ];

    public static function classify(string $text): string
    {
        foreach (self::$map as $type => $conditions) {
            if (in_array($text, $conditions)) {
                return $type;
            }
        }

        return 'cloud'; // fallback
    }
}
