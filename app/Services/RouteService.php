<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openrouteservice.org/v2/directions/driving-car/geojson';

    public function __construct()
    {
        $this->apiKey = env('OPENROUTE_SERVICE_API_KEY');

        if (empty($this->apiKey)) {
            throw new \Exception('OPENROUTE_SERVICE_API_KEY is not set in .env');
        }
    }

    public function getDrivingRoute(array $coordinates): array
    {
        if (count($coordinates) < 2) {
            throw new \InvalidArgumentException('At least 2 coordinates required');
        }

        try {

            $resp = Http::timeout(120)
            ->connectTimeout(30)
            ->withHeaders([
                'Accept' => 'application/json, application/geo+json, application/gpx+xml',
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => $this->apiKey,
            ])
            ->post($this->baseUrl, [
                'coordinates'  => $coordinates,
                'instructions' => false,
            ]);

            if ($resp->failed()) {

                $body = $resp->json('error') ?? $resp->body();
                Log::error('ORS API failed', [
                    'code' => $resp->status(),
                    'msg'  => $body
                ]);
                return [];
            }

            $geo = $resp->json('features.0.geometry');

            if (!isset($geo['coordinates']) || $geo['type'] !== 'LineString') {
                Log::error('Invalid GeoJSON geometry', ['geo' => $geo]);
                return [];
            }

            $route = array_map(fn($pt) => [
                'lat' => $pt[1],
                'lng' => $pt[0]
            ], $geo['coordinates']);

            $markers = array_map(fn($pt) => [
                'lat' => $pt[1],
                'lng' => $pt[0]
            ], $coordinates);

            return [
                'markers'         => $markers,
                'route'           => $route,
                'routePointCount' => count($route),
            ];
        } catch (\Throwable $e) {

            Log::error('RouteService Exception: ' . $e->getMessage());
            return [];
        }
    }
}
