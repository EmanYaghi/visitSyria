<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Facades\Cache;

class TranslateJsonResponse
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $lang = strtolower($request->header('Accept-Language', 'ar'));
        if ($lang === 'ar') {
            return $response;
        }

        $data = $response->getData(true);
        $data = $this->translateArray($data, $lang);
        $response->setData($data);

        return $response;
    }

    protected function translateArray(array $data, string $lang): array
    {
        $translator = new GoogleTranslate($lang);
        $translator->setOptions(['timeout' => 3]);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->translateArray($value, $lang);
                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $text = trim($value);

            if ($text === '' || mb_strlen($text) < 3) {
                continue;
            }

            if (ctype_digit($text)) {
                continue;
            }

            if (preg_match('/https?:\/\/\S+/i', $text)) {
                continue;
            }

            $cacheKey = "tr_{$lang}_" . md5($text);
            $data[$key] = Cache::rememberForever($cacheKey, function () use ($translator, $text) {
                return $translator->translate($text);
            });
        }

        return $data;
    }
}
