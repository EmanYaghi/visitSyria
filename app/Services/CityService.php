<?php
namespace App\Services;

use App\Repositories\CityRepository;

class CityService
{
    protected $cityRepository;

    public function __construct(CityRepository $cityRepository)
    {
        $this->cityRepository = $cityRepository;
    }

    public function getAllCities()
    {
        $cities = $this->cityRepository->getAll();
        return $cities->map(function ($city) {
            $data = $city->only(['id', 'name', 'description']);
            $media = $city->media->map(function ($media) {
                return [
                    'id' => $media->id,
                    'city_id' => $media->city_id,
                    'url' => asset($media->url),
                ];
            });
            $data['media'] = $media;
            return $data;
        });
    }

    public function getCityDetails($id)
    {
        $city = $this->cityRepository->find($id);
        $data = $city->only(['id', 'name', 'description']);
        $media = $city->media->map(function ($media) {
            return [
                'id' => $media->id,
                'city_id' => $media->city_id,
                'url' => asset($media->url),
            ];
        });
        $data['media'] = $media;
        return $data;
    }
}
