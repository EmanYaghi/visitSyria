<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ItineraryResource extends JsonResource
{
    public function toArray($request)
    {
        // $this->resource هو موديل Itinerary أو array
        $id = $this->resource->id ?? ($this->resource['id'] ?? null);
        $title = $this->resource->title ?? ($this->resource['title'] ?? null);
        $timelines = $this->resource->timelines ?? ($this->resource['timelines'] ?? []);
        $created = $this->resource->created_at ?? ($this->resource['created_at'] ?? null);
        $createdDate = null;
                if ($created) {
                    try {
                        $createdDate = $created instanceof Carbon
                            ? $created->toDateString()
                            : Carbon::parse($created)->toDateString();
                    } catch (\Throwable $e) {
                        $createdDate = null;
                    }
                }
        $normalized = array_map(function($day) {
            return [
                'day_number' => (int)($day['day_number'] ?? 0),
                'sections' => array_map(function($sec) {
                    return [
                        'time' => $sec['time'] ?? '',
                        'title' => $sec['title'] ?? '',
                        'description' => array_values(array_filter($sec['description'] ?? [], fn($d) => $d !== null && $d !== ''))
                    ];
                }, $day['sections'] ?? [])
            ];
        }, $timelines);

        return [
            'id' => $id,
            'title' => $title,
            'timelines' => $normalized,
            'created_at' => $createdDate,
        ];
    }
}
