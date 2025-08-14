<?php

//---------------------------------------------

// Microsoft Phi-4 Model

//---------------------------------------------


// namespace App\Services;

// use Illuminate\Support\Facades\Http;

// class AIChatService
// {
//     // protected string $endpoint = 'https://router.huggingface.co/nebius/v1/chat/completions';
//     protected string $endpoint = 'https://router.huggingface.co/v1/chat/completions';
//     protected string $apiKey;

//     public function __construct()
//     {
//         $this->apiKey = env('HUGGINGFACE_API_KEY');
//     }

//     public function generateItinerary(array $messages, string $model = 'microsoft/phi-4'): array|string
//     {
//         $response = Http::withHeaders([
//             'Authorization' => 'Bearer ' . $this->apiKey,
//             'Content-Type' => 'application/json',
//         ])->post($this->endpoint, [
//             'model' => $model,
//             'stream' => false,
//             'messages' => $messages,
//         ]);

//         if ($response->successful()) {
//             return $response->json();
//         }

//         return [
//             'error' => true,
//             'status' => $response->status(),
//             'message' => $response->body(),
//         ];
//     }
// }
//---------------------------------------------

// DeepSeek R1 Model

//---------------------------------------------
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIChatService
{
    protected string $endpoint = 'https://router.huggingface.co/v1/chat/completions';
    protected string $apiKey;
    protected array $tripTypes = [
        'طبيعة و مناظر خلابة' => 'nature',
        'ترفيهية و تسوق' => 'family, shopping',
        'ثقافية و تاريخية' => 'culture, history',
        'دينية و روحانية' => 'spiritual',
        'مزيج من كل شيء' => 'mix of all trips types',
    ];

    protected array $activities = [
        'مغامرات و نشاط عالي' => 'high activity',
        'متوسط النشاط' => 'medium activity',
        'مريح و هادئ' => 'relaxing',
    ];

    protected array $companions = [
        'لوحدي' => 'alone',
        'مع شريك/زوج' => 'with my partner',
        'مع أصدقاء' => 'with friends',
        'مع العائلة بما فيهم أطفال' => 'with my family including children',
    ];

    protected array $hotelStars = [
        'لا أريد' => 'no',
        'نجمة واحدة' => '1-star',
        'نجمتين' => '2-star',
        '3 نجوم' => '3-star',
        '4 نجوم' => '4-star',
        '5 نجوم' => '5-star',
    ];

    protected array $durationMap = [
        'يوم واحد' => '1 day',
        '2-3 أيام' => '2-3 days',
        '4-7 أيام' => '4-7 days',
        'أكثر من أسبوع' => 'more than a week',
    ];

    protected array $cityMap = [
        'دمشق' => 'Damascus',
        'ريف دمشق' => 'Rural Damascus',
        'حمص' => 'Homs',
        'حماة' => 'Hama',
        'اللاذقية' => 'Lattakia',
        'طرطوس' => 'Tartous',
        'إدلب' => 'Idlib',
        'السويداء' => 'Sweida',
        'درعا' => 'Daraa',
        'دير الزور' => 'Deir ez-Zor',
        'الحسكة' => 'Hasakah',
        'حلب' => 'Aleppo',
        'القنيطرة' => 'Al-Quneitra',
        'الرقة' => 'Ar-Raqqah'
    ];

    protected array $typeOfPlaces = [
        'طبيعة و جبال' => 'nature and mountains',
        'أسواق و مراكز تجارية' => 'markets and shopping centers',
        'متاحف و آثار' => 'museums and archaeological sites',
        'أماكن دينية' => 'religious sites',
        'مطاعم و مقاهي مميزة' => 'restaurants and unique cafes',
    ];

    public function __construct()
    {
        $this->apiKey = env('HUGGINGFACE_API_KEY');
    }

    public function generateTripItinerary(array $requestData): array
    {
        $messages = $this->prepareMessages($requestData);
        $response = $this->callAI($messages);
        return $this->processResponse($response);
    }


    protected function prepareMessages(array $data): array
    {
        return [
            [
                "role" => "system",
                "content" => $this->getSystemMessage()
            ],
            [
                "role" => "user",
                "content" => $this->buildUserContent($data)
            ]
        ];
    }

    protected function getSystemMessage(): string
    {
        return <<<EOT
You are a Syrian tourism planning assistant. Given the user's preferences, generate a structured JSON itinerary in Arabic.

Instructions:
- Use this exact JSON format:
{
  "Trip": {
    "Title": "عنوان عام للرحلة باللغة العربية", // General trip title in Arabic
      "Day1": [
        {
          "Time": "08:00AM - 10:00AM",
          "Title": "Activity Title in Arabic",
          "Content": [
            "Detailed description point 1 in Arabic",
            "Detailed description point 2 in Arabic"
          ]
        },
        ...
      ],
      "Day2": [ ... ]
    }
}
- Keep keys in English: Trip, Day1, Time, Title, Content
- The general Title should reflect the trip's main theme (e.g., "رحلة ثقافية في دمشق لمدة ٣ أيام")
- All content values (Title and Content array) must be in formal Arabic
- Use exact clock time ranges (e.g., "9:30AM - 11:00AM")
- Realistic time estimates:
  - Sightseeing: 1.5-2 hours
  - Museum visits: 1.5-2 hours
  - Meals: 1-1.5 hours
  - Relaxation: 1.5-2.5 hours
- Include ONLY real, verified places in Syria. If unsure, omit it.
- Use only famous landmarks: قلعة حلب، الجامع الأموي، المتحف الوطني، سوق الحميدية,مقهى الكمال,سينما سيتي في دمشق,ساحة الساعة في حمص, etc.
- Avoid vague or fictional names
- Customize the trip depeneding on user input to be professional and nice trip
- Use professional, tourism-quality Arabic
- Return ONLY valid JSON without any additional text
EOT;
    }

    protected function buildUserContent(array $data): string
    {
        $duration = $data['duration'][0] ?? '3 days';
        $duration = $this->durationMap[$duration] ?? $duration;

        $themes = collect($data['type_of_trips'] ?? [])
            ->map(fn($t) => $this->tripTypes[$t] ?? $t)
            ->implode(', ');

        $travelWith = collect($data['travel_with'] ?? [])
            ->map(fn($w) => $this->companions[$w] ?? $w)
            ->implode(', ');

        $cities = collect($data['cities'] ?? [])
            ->map(fn($c) => $this->cityMap[$c] ?? $c)
            ->implode(' and ');

        $places = collect($data['type_of_places'] ?? [])
            ->map(fn($p) => $this->typeOfPlaces[$p] ?? $p)
            ->implode(', ');

        $hotel = $data['sleeping_in_hotel'][0] ?? null;
        $hotelText = ($hotel && isset($this->hotelStars[$hotel]) && $this->hotelStars[$hotel] !== 'no')
            ? 'Yes, we want to stay in a ' . $this->hotelStars[$hotel] . ' hotel.'
            : 'No hotel stay.';

        $activityMood = collect($data['average_activity'] ?? [])
            ->map(fn($a) => $this->activities[$a] ?? $a)
            ->implode(', ');

        return "I am planning a {$duration} trip "
            . ($travelWith ? "with {$travelWith} " : "")
            . "to {$cities}. "
            . ($themes ? "Our main travel interests are: {$themes}. " : "")
            . ($places ? "We are specifically interested in visiting places like: {$places}. " : "")
            . "{$hotelText} "
            . ($activityMood ? "We prefer a travel pace that is {$activityMood}. " : "")
            . "Please suggest a detailed, realistic daily itinerary using only real, verified, and famous places that exist in the selected cities. "
            . "Make sure the activities match our interests and travel style. This plan should be practical, based on real locations, and reflect our choices accurately.";
    }


    protected function callAI(array $messages): array
    {
        try {
            $response = Http::timeout(120)
                ->retry(2, 500)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->endpoint, [
                    'model' => 'deepseek-ai/DeepSeek-R1:nebius',
                    'stream' => false,
                    'messages' => $messages,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'error' => true,
                'status' => $response->status(),
                'message' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('AI API Connection Error', ['error' => $e->getMessage()]);
            return [
                'error' => true,
                'status' => 503,
                'message' => 'Service unavailable: ' . $e->getMessage(),
            ];
        }
    }

    protected function processResponse(array $response): array
    {
        if (isset($response['error'])) {
            return [
                'success' => false,
                'error' => 'AI service error',
                'status' => $response['status'],
                'message' => $response['message']
            ];
        }

        $content = $response['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            return [
                'success' => false,
                'error' => 'Empty AI response',
                'response' => $response
            ];
        }

        $cleaned = $this->cleanResponse($content);
        $parsed = $this->parseResponse($cleaned);

        if (!$parsed['success']) {
            return $parsed;
        }

        return [
            'success' => true,
            'Trip' => $parsed['Trip']
        ];
    }

    protected function cleanResponse(string $content): string
    {
        return preg_replace([
            '/^<think>.*?<\/think>\n?/si',
            '/^(?:json)?\n/m',
            '/$/m',
            '/^[\s\S]*?({[\s\S]*})[\s\S]*$/s'
        ], ['', '', '', '$1'], trim($content));
    }

    protected function parseResponse(string $content): array
    {
        try {
            $parsed = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($parsed['Trip']) || !is_array($parsed['Trip'])) {
                throw new \JsonException('Missing "Trip" object');
            }

            return [
                'success' => true,
                'Trip' => $parsed['Trip']
            ];
        } catch (\JsonException $e) {
            Log::error('JSON Parse Error', [
                'error' => $e->getMessage(),
                'content' => $content
            ]);

            return [
                'success' => false,
                'error' => 'Invalid JSON format',
                'message' => $e->getMessage(),
                'raw' => $content
            ];
        }
    }
}
