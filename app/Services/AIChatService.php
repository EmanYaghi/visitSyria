<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIChatService
{
    protected string $endpoint;
    protected string $apiKey;

    // --- maps (احتفظت بالقوائم التي زودتني بها) ---
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
        $this->endpoint = env('HUGGINGFACE_ENDPOINT', 'https://router.huggingface.co/v1/chat/completions');
    }

    /**
     * Main entry: generate itinerary (title + timelines)
     *
     * @param array $requestData
     * @return array
     */
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
        // الصيغة تطلب title + timelines فقط وبصرامة
        return <<<EOT
You are a Syrian tourism planning assistant. Given the user's preferences, return ONLY valid JSON that exactly matches the schema below (no extra text, no explanation).

Schema (must follow exactly):
{
  "title": "عنوان عام للرحلة باللغة العربية",
  "timelines": [
    {
      "day_number": 1,
      "sections": [
        {
          "time": "09:00",
          "title": "عنوان النشاط باللغة العربية",
          "description": [
            "نقطة وصفية 1 باللغة العربية",
            "نقطة وصفية 2 باللغة العربية"
          ]
        }
      ]
    }
  ]
}

Rules:
- Keys must be exactly: title, timelines, day_number, sections, time, title, description.
- "title" must be a short Arabic title summarizing the trip (e.g., "رحلة ثقافية في دمشق لمدة ٣ أيام").
- "day_number" must be integer starting at 1.
- "time" must be in 24-hour HH:MM format (e.g., "09:30", "14:15").
- All text values must be in formal Arabic.
- Return only JSON content (no backticks, no code fences, no commentary).
- If a day has no activities, return "sections": [] for that day.
- Ensure JSON parses without errors.
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
            . "Return JSON that includes a short Arabic title and a 'timelines' array following the system schema.";
    }

    /**
     * Call Hugging Face (or router) API
     *
     * @param array $messages
     * @return array  decoded response or ['raw' => string] on non-json response
     */
    protected function callAI(array $messages): array
    {
        try {
            $model = env('HUGGINGFACE_MODEL', 'deepseek-ai/DeepSeek-R1:nebius');

            $response = Http::timeout(120)
                ->retry(2, 500)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->endpoint, [
                    'model' => $model,
                    'stream' => false,
                    'messages' => $messages,
                ]);

            // Logging for dev (remove or lower level in production)
            Log::info('HF response status: ' . $response->status());
            Log::debug('HF response body: ' . $response->body());

            if ($response->successful()) {
                try {
                    // try decode structured json
                    return $response->json();
                } catch (\Throwable $e) {
                    // return raw body for parsing later
                    return ['raw' => $response->body()];
                }
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

    /**
     * Process response from callAI() and return normalized array:
     * ['success'=>true, 'title'=>..., 'timelines'=>..., 'raw'=>..., 'model'=>...]
     */
    protected function processResponse(array $response): array
    {
        if (isset($response['error']) && $response['error'] === true) {
            return [
                'success' => false,
                'error' => 'AI service error',
                'status' => $response['status'] ?? null,
                'message' => $response['message'] ?? null
            ];
        }

        // content could be choices[0].message.content OR raw string
        $content = $response['choices'][0]['message']['content'] ?? $response['raw'] ?? null;

        if (!$content) {
            return [
                'success' => false,
                'error' => 'Empty AI response',
                'response' => $response
            ];
        }

        $cleaned = $this->cleanResponse((string)$content);
        $parsed = $this->parseResponse($cleaned);

        if (!$parsed['success']) {
            return $parsed; // contains error + raw
        }

        $title = $parsed['title'] ?? null;
        $timelines = $parsed['timelines'] ?? null;

        if (is_array($timelines)) {
            return [
                'success' => true,
                'title' => $title ?? null,
                'timelines' => $timelines,
                'raw' => $cleaned,
                'model' => env('HUGGINGFACE_MODEL')
            ];
        }

        // Backwards compatibility: if Trip exists convert
        if (isset($parsed['Trip']) && is_array($parsed['Trip'])) {
            $timelines = $this->transformTripToTimelines($parsed['Trip']);
            return [
                'success' => true,
                'title' => $title ?? null,
                'timelines' => $timelines,
                'raw' => $cleaned,
                'model' => env('HUGGINGFACE_MODEL')
            ];
        }

        return [
            'success' => false,
            'error' => 'Unexpected response structure',
            'raw' => $cleaned
        ];
    }

    /**
     * Clean model output and extract first JSON object if possible
     *
     * @param string $content
     * @return string
     */
    protected function cleanResponse(string $content): string
    {
        $content = trim($content);

        // Remove <think> blocks sometimes used by models
        $content = preg_replace('/^<think>.*?<\/think>\s*/si', '', $content);

        // Extract first JSON object {...}
        if (preg_match('/({[\s\S]*})/s', $content, $m)) {
            return $m[1];
        }

        return $content;
    }

    /**
     * Parse cleaned JSON string
     *
     * @param string $content
     * @return array
     */
    protected function parseResponse(string $content): array
    {
        try {
            $parsed = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            $out = ['success' => true];

            if (isset($parsed['title'])) {
                $out['title'] = $parsed['title'];
            }

            if (isset($parsed['timelines']) && is_array($parsed['timelines'])) {
                $out['timelines'] = $parsed['timelines'];
                return $out;
            }

            if (isset($parsed['Trip']) && is_array($parsed['Trip'])) {
                $out['Trip'] = $parsed['Trip'];
                return $out;
            }

            // If top-level array of day objects or a single day object
            if (isset($parsed[0]['day_number']) || isset($parsed['day_number'])) {
                $out['timelines'] = is_array($parsed) ? $parsed : [$parsed];
                return $out;
            }

            throw new \JsonException('Missing "timelines" or "Trip" object');
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

    /**
     * Convert older Trip format (Day1, Day2...) to timelines
     *
     * @param array $trip
     * @return array
     */
    protected function transformTripToTimelines(array $trip): array
    {
        $timelines = [];

        foreach ($trip as $key => $value) {
            if (preg_match('/^Day(\d+)$/i', $key, $m) && is_array($value)) {
                $dayNumber = (int)$m[1];
                $sections = [];

                foreach ($value as $activity) {
                    $time = $activity['Time'] ?? null;
                    $title = $activity['Title'] ?? null;
                    $description = $activity['Content'] ?? [];

                    $normalizedTime = $this->normalizeTime($time);

                    $sections[] = [
                        'time' => $normalizedTime ?: '09:00',
                        'title' => $title ?: '',
                        'description' => is_array($description) ? array_values($description) : [strval($description)]
                    ];
                }

                $timelines[] = [
                    'day_number' => $dayNumber,
                    'sections' => $sections
                ];
            }
        }

        // If no DayN keys found, try fallback: treat top-level arrays as day1 sections
        if (empty($timelines)) {
            $sections = [];
            foreach ($trip as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $act) {
                        $sections[] = [
                            'time' => $this->normalizeTime($act['Time'] ?? null) ?: '09:00',
                            'title' => $act['Title'] ?? '',
                            'description' => is_array($act['Content'] ?? []) ? ($act['Content'] ?? []) : [($act['Content'] ?? '')]
                        ];
                    }
                }
            }

            $timelines[] = [
                'day_number' => 1,
                'sections' => $sections
            ];
        }

        usort($timelines, fn($a, $b) => $a['day_number'] <=> $b['day_number']);

        return $timelines;
    }

    /**
     * Normalize time strings to HH:MM 24-hour format
     *
     * @param string|null $time
     * @return string|null
     */
    protected function normalizeTime(?string $time): ?string
    {
        if (!$time) return null;
        $time = trim($time);

        // Already HH:MM
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            [$h, $m] = explode(':', $time);
            $h = str_pad($h, 2, '0', STR_PAD_LEFT);
            return "{$h}:{$m}";
        }

        // Formats like "08:00AM - 10:00AM" or "9:30AM"
        if (preg_match('/(\d{1,2}:\d{2})(?:\s*)?(AM|PM)?/i', $time, $m)) {
            $t = $m[1];
            $ampm = $m[2] ?? null;
            if ($ampm) {
                $format = 'h:iA';
                $raw = strtoupper($t . $ampm);
                $dt = \DateTime::createFromFormat($format, $raw);
                if ($dt) return $dt->format('H:i');
            }
            if (preg_match('/^\d{1,2}:\d{2}$/', $t)) {
                return str_pad($t, 5, '0', STR_PAD_LEFT);
            }
        }

        return null;
    }
}
