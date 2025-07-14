<?php
namespace App\Repositories;

use App\Models\City;

class CityRepository
{
    public function getAll()
    {
        return City::with('media')->get();
    }

    public function find($id)
    {
        return City::with('media')->findOrFail($id);
    }

}
