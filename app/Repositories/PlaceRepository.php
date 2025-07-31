<?php
namespace App\Repositories;

use App\Models\Place;

class PlaceRepository
{
    public function getAll($filters = [])
    {
        return Place::when(isset($filters['type']), fn($q) => $q->where('type', $filters['type']))
                    ->when(isset($filters['city_id']), fn($q) => $q->where('city_id', $filters['city_id']))
                    ->latest()
                    ->get();
    }
    public function getTopRatedPlaces($filters = [])
    {
        return Place::with(['ratings'])
            ->withAvg('ratings as ratings_avg', 'rating_value') 
            ->when(isset($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->orderBy('ratings_avg', 'desc')
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
    public function getTouristPlacesByClassificationAndCity($classification, $cityId)
        {
        return Place::withAvg('ratings as ratings_avg', 'rating_value')
                    ->where('type', 'tourist')
                    ->where('classification', $classification)
                    ->where('city_id', $cityId)
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
        return Place::findOrFail($id);
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
