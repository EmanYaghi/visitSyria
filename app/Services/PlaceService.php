<?php
namespace App\Services;

use App\Repositories\PlaceRepository;
use Illuminate\Support\Str;

class PlaceService
{
    protected $placeRepo;

    public function __construct(PlaceRepository $placeRepo)
    {
        $this->placeRepo = $placeRepo;
    }

    public function getAll($filters)
    {
        return $this->placeRepo->getAll($filters);
    }

     public function store(array $data)
    {
        if ($data['type'] == 'tourist') {
            $data['phone'] = null;
            $data['country_code'] = null;
        } elseif ($data['type'] == 'hotel' || $data['type'] == 'restaurant') {
            if (empty($data['phone'])) {
                return response()->json(['error' => 'Phone number is required for hotels and restaurants'], 400);
            }
        }
        $data['id'] = $this->generatePlaceId();
        return $this->placeRepo->create($data);
    }
    public function update($id, array $data)
    {
        if ($data['type'] == 'tourist') {
            $data['phone'] = null;
            $data['country_code'] = null;
        } elseif ($data['type'] == 'hotel' || $data['type'] == 'restaurant') {
            if (empty($data['phone'])) {
                return response()->json(['error' => 'Phone number is required for hotels and restaurants'], 400);
            }
        }
        return $this->placeRepo->update($id, $data);
    }
    private function generatePlaceId()
    {
        $lastPlace = $this->placeRepo->getLastPlace();
        $lastId = $lastPlace ? (int)substr($lastPlace->id, -6) : 0;
        return str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }

    public function getById($id)
    {
        return $this->placeRepo->findById($id);
    }

}
