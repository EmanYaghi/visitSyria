<?php

namespace App\Repositories;

use App\Models\Place;

class PlaceRepository
{
    public function getByIdWithDetails($id)
    {
        return Place::with([
            'media',
            'latestComments.user.profile',
            'ratings',
            'comments'
        ])->findOrFail($id);
    }

    public function findByTypeExceptId(string $type, int $excludeId)
    {
        return Place::where('type', $type)
                    ->where('id', '!=', $excludeId)
                    ->latest()
                    ->get();
    }

public function getAll($filters = [])
{
    return Place::with([
            'media',
            'ratings',
            'latestComments' => function ($q) {
                $q->limit(3);
            },
            'latestComments.user.profile',
            'latestComments.user.media'
        ])
        ->when(isset($filters['type']), fn($q) => $q->where('type', $filters['type']))
        ->when(isset($filters['city_id']), fn($q) => $q->where('city_id', $filters['city_id']))
        ->latest()
        ->get();
}

    public function getTouristPlacesByClassificationAndCity($classification, $cityId)
    {
        return Place::withAvg('ratings as ratings_avg', 'rating_value')
                    ->where([
                        ['type', 'tourist'],
                        ['classification', $classification],
                        ['city_id', $cityId],
                    ])
                    ->latest()
                    ->get();
    }

    public function getTopRatedPlaces(array $filters = [])
    {
        return Place::withAvg('ratings as ratings_avg', 'rating_value')
                    ->when(isset($filters['type']), fn($q) => $q->where('type', $filters['type']))
                    ->when(isset($filters['city_id']), fn($q) => $q->where('city_id', $filters['city_id']))
                    ->orderByDesc('ratings_avg')
                    ->limit(10)
                    ->get();
    }

    public function getTouristPlacesByClassification($classification)
    {
        return Place::where('type', 'tourist')
                    ->where('classification', $classification)
                    ->latest()
                    ->get();
    }

    public function getRestaurantsByCityName($cityName)
    {
        return Place::where('type', 'restaurant')
            ->whereHas('city', function($q) use ($cityName) {
                $q->where('name', $cityName);
            })
            ->with('city')
            ->latest()
            ->get();
    }

    public function getHotelsByCityName($cityName)
    {
        return Place::where('type', 'hotel')
            ->whereHas('city', function($q) use ($cityName) {
                $q->where('name', $cityName);
            })
            ->with('city')
            ->latest()
            ->get();
    }

    public function findById($id)
    {
        return Place::with(['media'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Place::create($data);
    }

    public function update($id, array $data)
    {
        $place = $this->findById($id);
        $place->update($data);
        return $place;
    }

    public function delete($id)
    {
        $place = $this->findById($id);
        return $place->delete();
    }

    public function getLastPlace()
    {
        return Place::latest()->first();
    }
}
