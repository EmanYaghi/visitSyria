<?php

namespace App\Services;

use App\Http\Resources\PlaceResource;
use App\Repositories\PlaceRepository;
use App\Models\Media;
use App\Models\Place;
use App\Models\Save;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PlaceService
{
    protected PlaceRepository $placeRepo;

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

    public function getTopRatedTouristPlaces(array $filters = [])
    {
        $filters['type'] = 'tourist';
        $places = $this->placeRepo->getTopRatedPlaces($filters);
        foreach ($places as $index => $place) {
            $place->rank = $index + 1;
        }
        return $places;
    }

    public function getTouristPlacesByClassification($classification)
    {
        return $this->placeRepo->getTouristPlacesByClassification($classification);
    }

    public function getRestaurantsByCityName($cityName)
    {
        return $this->placeRepo->getRestaurantsByCityName($cityName);
    }

    public function getHotelsByCityName($cityName)
    {
        return $this->placeRepo->getHotelsByCityName($cityName);
    }

    public function getTouristPlacesByCityName(string $cityName)
{
    return $this->placeRepo->getTouristPlacesByCityName($cityName);
}


    public function getTouristPlacesByClassificationAndCity($classification, $cityId)
    {
        $places = $this->placeRepo->getTouristPlacesByClassificationAndCity($classification, $cityId);
        $topRatedIds = $this->placeRepo
            ->getTopRatedPlaces(['type' => 'tourist', 'city_id' => $cityId])
            ->pluck('id')
            ->toArray();
        foreach ($places as $place) {
            $place->rank = ($index = array_search((int)$place->getKey(), $topRatedIds)) !== false ? $index + 1 : null;
        }
        return $places;
    }

public function storeImages($images, $place)
{
    $created = [];
    foreach ($images as $image) {
        $path = $image->store('places', 'public');
        $m = Media::create([
            'place_id' => $place->id,
            'url' => $path,
        ]);
        $created[] = $m;
    }

    $place->load('media');

    return collect($created);
}

     public function replaceImages($images, $place)
    {
        // enforce max 4
        if (!is_array($images) && $images instanceof \Illuminate\Support\Collection) {
            $images = $images->all();
        }
        $count = is_array($images) ? count($images) : 0;
        if ($count > 4) {
            throw new \InvalidArgumentException('Cannot upload more than 4 images.');
        }

        // delete files and records for existing media
        $place->loadMissing('media');
        foreach ($place->media as $media) {
            try {
                if (!empty($media->url)) {
                    Storage::disk('public')->delete(ltrim($media->url, '/'));
                }
            } catch (\Throwable $e) {
                // ignore deletion errors, continue to delete record
            }
            try {
                $media->delete();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // store new images
        $created = [];
        foreach ($images as $image) {
            $path = $image->store('places', 'public');
            $m = Media::create([
                'place_id' => $place->id,
                'url' => $path,
            ]);
            $created[] = $m;
        }

        // reload media relation
        $place->load('media');

        return collect($created);
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

    public function getPlaceDetails($id)
    {
        return $this->placeRepo->getByIdWithDetails($id);
    }

    public function getSimilarPlaces(int $id, string $type)
    {
        $places = $this->placeRepo->findByTypeExceptId($type, $id);
        $topRated = $this->getTopRatedTouristPlaces([]);
        $rankMap = $topRated->pluck('rank', 'id')->toArray();
        return $places->map(function ($place) use ($rankMap) {
            $place->avg_rating = $place->ratings()->avg('rating_value') ?: 0;
            $place->rank = $rankMap[(int)$place->getKey()] ?? null;
            return $place;
        });
    }

    public function getTopRatedTouristIds(int $limit = 10, int $ttlSeconds = 300): array
    {
        return Cache::remember("top_tourist_place_ids_{$limit}", $ttlSeconds, function () use ($limit) {
            $places = $this->placeRepo->getTopRatedPlaces(['type' => 'tourist']);
            return $places->take($limit)->pluck('id')->map(fn($id) => (int)$id)->toArray();
        });
    }

    public function annotateWithGlobalTouristRank($places, int $limit = 10): void
    {
        if (! $places instanceof Collection) return;
        $topIds = $this->getTopRatedTouristIds($limit);
        $rankMap = [];
        foreach ($topIds as $i => $id) {
            $rankMap[(int)$id] = $i + 1;
        }
        foreach ($places as $place) {
            $place->rank = $rankMap[(int)$place->getKey()] ?? null;
        }
    }

    public function annotateSingleWithGlobalTouristRank($place, int $limit = 10): void
    {
        if (! $place) return;
        $topIds = $this->getTopRatedTouristIds($limit);
        $rankMap = [];
        foreach ($topIds as $i => $id) {
            $rankMap[(int)$id] = $i + 1;
        }
        $place->rank = $rankMap[(int)$place->getKey()] ?? null;
    }

    public function annotateIsSavedForCollection($places, $user): void
    {
        if (! $user || ! ($places instanceof Collection) || $places->isEmpty()) {
            foreach ($places as $p) {
                $p->is_saved = $user ? false : null;
            }
            return;
        }

        $placeIds = $places->map(fn($p) => (int)$p->getKey())->toArray();

        $savedPlaceIds = Save::where('user_id', $user->id)
            ->whereNotNull('place_id')
            ->whereIn('place_id', $placeIds)
            ->pluck('place_id')
            ->map(fn($v) => (int)$v)
            ->toArray();

        foreach ($places as $place) {
            $place->is_saved = in_array((int)$place->getKey(), $savedPlaceIds, true);
        }
    }

    public function annotateIsSavedForModel($place, $user): void
    {
        if (! $user) {
            $place->is_saved = null;
            return;
        }

        $place->is_saved = (bool) Save::where('user_id', $user->id)
            ->where('place_id', (int)$place->getKey())
            ->whereNotNull('place_id')
            ->exists();
    }

    public function getTopPlaces()
    {
        $places=Place::orderByDesc('rating')
            ->limit(3)
            ->get();
        return [
            "places" => PlaceResource::collection($places),
            "message" => 'these are top 3 places',
            'code' => 200
        ];
    }
}
