<?php
namespace App\Services;

use App\Repositories\PlaceRepository;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;

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
        $data['classification'] = $this->normalizeClassification($data);
        $data['phone'] = $this->normalizePhone($data);
        $data['id'] = $this->generatePlaceId();
        return $this->placeRepo->create($data);
    }

    public function update(int $id, array $data)
    {
        if (isset($data['type'])) {
            $data['classification'] = $this->normalizeClassification($data);
            $data['phone'] = $this->normalizePhone($data);
        }
        return $this->placeRepo->update($id, $data);
    }

    public function delete($id)
    {
        return $this->placeRepo->delete($id);
    }

public function getRestaurants($filters = [])
{
    $filters['type'] = 'restaurant';
    return $this->placeRepo->getAll($filters);
}
    public function getHotels($filters = [])
    {
        $filters['type'] = 'hotel';
        return $this->placeRepo->getAll($filters);
    }

    public function getTouristPlaces($filters = [])
    {
        $filters['type'] = 'tourist';
        return $this->placeRepo->getAll($filters);
    }
    public function getTopRatedTouristPlaces($filters = [])
    {
        $filters['type'] = 'tourist';
        return $this->placeRepo->getTopRatedPlaces($filters);
    }
    public function getTouristPlacesByClassification($classification)
    {
        return $this->placeRepo->getTouristPlacesByClassification($classification);
    }
    public function getRestaurantsByCityName($cityName)
    {
        return $this->placeRepo->getRestaurantsByCityName($cityName);
    }
public function getTouristPlacesByClassificationAndCity($classification, $cityId)
{
    $places = $this->placeRepo->getTouristPlacesByClassificationAndCity($classification, $cityId);

    $topRatedIds = $this->placeRepo
        ->getTopRatedPlaces(['type' => 'tourist', 'city_id' => $cityId])
        ->pluck('id')
        ->toArray();

    foreach ($places as $place) {
        $place->rank = ($index = array_search((int)$place->id, $topRatedIds)) !== false ? $index + 1 : null;
    }

    return $places;
}



    public function getHotelsByCityName($cityName)
    {
        return $this->placeRepo->getHotelsByCityName($cityName);
    }
    public function storeImages($images, $place)
    {
        $imageUrls = [];
        foreach ($images as $image) {
            $path = $image->store('places', 'public');
            Media::create([
                'place_id' => $place->id,
                'url' => $path,
            ]);
            $imageUrls[] = Storage::disk('public')->url($path);
        }
        return $imageUrls;
    }


    protected function normalizeClassification(array $d): ?string
    {
        return in_array($d['type'], ['hotel', 'restaurant'])
            ? null
            : ($d['classification'] ?? null);
    }

    protected function normalizePhone(array $d): ?string
    {
        return in_array($d['type'], ['hotel', 'restaurant'])
            ? ($d['phone'] ?? throw new \InvalidArgumentException('Phone required.'))
            : null;
    }

    protected function generatePlaceId(): string
    {
        $last = $this->placeRepo->getLastPlace();
        $n = $last ? (int)substr($last->id, -6) : 0;
        return str_pad($n + 1, 6, '0', STR_PAD_LEFT);
    }

    public function getById($id)
    {
        return $this->placeRepo->findById($id)->load('media');
    }
}
