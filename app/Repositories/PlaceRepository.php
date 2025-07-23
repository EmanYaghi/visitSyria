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
