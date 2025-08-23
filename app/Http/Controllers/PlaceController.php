<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceStoreRequest;
use App\Http\Requests\PlaceUpdateRequest;
use App\Http\Resources\PlaceResource;
use App\Models\City;
use App\Models\Place;
use App\Services\PlaceService;
use Illuminate\Http\Request;
use Throwable;

class PlaceController extends Controller
{
    protected PlaceService $placeService;

    public function __construct(PlaceService $placeService)
    {
        $this->placeService = $placeService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['type', 'city_id']);
        $places = $this->placeService->getAll($filters);
        $user = $request->user('api');
        $this->placeService->annotateIsSavedForCollection($places, $user);
        $this->placeService->annotateWithGlobalTouristRank($places);
        return PlaceResource::collection($places);
    }

    public function cityPlaces(Request $request, $cityName)
    {
        $city = City::where('name', $cityName)->first();
        if (!$city) {
            return response()->json(['message' => 'City not found'], 404);
        }
        $filters = $request->only(['type']);
        $filters['city_id'] = $city->id;
        $places = $this->placeService->getAll($filters);
        $user = $request->user('api');
        $this->placeService->annotateIsSavedForCollection($places, $user);
        $this->placeService->annotateWithGlobalTouristRank($places);
        return PlaceResource::collection($places);
    }

    public function similarPlaces($id)
    {
        $place = Place::findOrFail($id);
        $similar = $this->placeService->getSimilarPlaces($id, $place->type);
        $user = auth('api')->user();
        $this->placeService->annotateIsSavedForCollection($similar, $user);
        $this->placeService->annotateWithGlobalTouristRank($similar);
        return PlaceResource::collection($similar);
    }

    public function show(Request $request, $id)
    {
        $place = $this->placeService->getPlaceDetails($id);
        if (!$place) {
            return response()->json(['message' => 'Place not found'], 404);
        }
        $this->placeService->annotateSingleWithGlobalTouristRank($place);
        if ($place->type === 'tourist') {
            $topPlaces = $this->placeService->getTopRatedTouristPlaces();
            foreach ($topPlaces as $index => $topPlace) {
                if ($topPlace->id === $place->id) {
                    $place->rank = $index + 1;
                    break;
                }
            }
        }
        $user = $request->user('api');
        $this->placeService->annotateIsSavedForModel($place, $user);
        return new PlaceResource($place);
    }

public function store(PlaceStoreRequest $request)
{
    $data = $request->validated();
    $data['city_id'] = City::where('name', $data['city_name'])->firstOrFail()->id;
    $place = $this->placeService->store($data);

    if ($request->hasFile('images')) {
        $this->placeService->storeImages($request->file('images'), $place);
    }

    // reload full place with media and relations
    $place = $this->placeService->getById($place->id);
    $user = $request->user('api');
    $this->placeService->annotateIsSavedForModel($place, $user);
    $this->placeService->annotateSingleWithGlobalTouristRank($place);

    return response()->json(['place' => new PlaceResource($place)], 201);
}

public function update(PlaceUpdateRequest $request, $id)
    {
        $data = $request->validated();
        if (isset($data['city_name'])) {
            $data['city_id'] = City::where('name', $data['city_name'])->firstOrFail()->id;
            unset($data['city_name']);
        }

        $place = $this->placeService->update($id, $data);

        if ($request->hasFile('images')) {
            $this->placeService->replaceImages($request->file('images'), $place);
        }

        // reload fresh model with media and relations
        $place = $this->placeService->getById($place->id);

        $user = $request->user('api');
        $this->placeService->annotateIsSavedForModel($place, $user);
        $this->placeService->annotateSingleWithGlobalTouristRank($place);

        return response()->json(['place' => new PlaceResource($place)]);
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $this->placeService->delete($id);
        return response()->json(['message' => 'Deleted successfully.']);
    }

    public function getRestaurants(Request $request)
    {
        $places = $this->placeService->getRestaurants();
        $user = $request->user('api');
        $this->placeService->annotateIsSavedForCollection($places, $user);
        $this->placeService->annotateWithGlobalTouristRank($places);
        return PlaceResource::collection($places);
    }

    public function getHotels(Request $request)
    {
        $places = $this->placeService->getHotels();
        $user = $request->user('api');
        $this->placeService->annotateIsSavedForCollection($places, $user);
        $this->placeService->annotateWithGlobalTouristRank($places);
        return PlaceResource::collection($places);
    }

    public function getTouristPlaces(Request $request)
    {
        $places = $this->placeService->getTouristPlaces();
        $user = $request->user('api');
        $this->placeService->annotateIsSavedForCollection($places, $user);
        $this->placeService->annotateWithGlobalTouristRank($places);
        return PlaceResource::collection($places);
    }

    public function getTopRatedTouristPlaces(Request $request)
    {
        $places = $this->placeService->getTopRatedTouristPlaces($request->all());
        $user = $request->user('api');
        $this->placeService->annotateIsSavedForCollection($places, $user);
        return PlaceResource::collection($places);
    }

    public function getTouristPlacesByClassification($classification)
    {
        $places = $this->placeService->getTouristPlacesByClassification($classification);
        $user = auth('api')->user();
        $this->placeService->annotateIsSavedForCollection($places, $user);
        $this->placeService->annotateWithGlobalTouristRank($places);
        return PlaceResource::collection($places);
    }

    public function getTouristPlacesByClassificationAndCity($classification, $cityName)
    {
        $city = City::where('name', $cityName)->first();
        if (!$city) { return response()->json(['message' => 'City not found'], 404); }
        $places = $this->placeService->getTouristPlacesByClassificationAndCity($classification, $city->id);
        $user = auth('api')->user();
        $this->placeService->annotateIsSavedForCollection($places, $user);
        $this->placeService->annotateWithGlobalTouristRank($places);
        return PlaceResource::collection($places);
    }

    public function getRestaurantsByCity(Request $request)
    {
        $request->validate([
            'city' => 'required|string|exists:cities,name'
        ]);
        $cityName = $request->input('city');
        $restaurants = $this->placeService->getRestaurantsByCityName($cityName);
        $user = $request->user('api');
        $this->placeService->annotateIsSavedForCollection($restaurants, $user);
        $this->placeService->annotateWithGlobalTouristRank($restaurants);
        return PlaceResource::collection($restaurants);
    }

    public function getHotelsByCity(Request $request)
    {
        $request->validate([
            'city' => 'required|string|exists:cities,name'
        ]);
        $cityName = $request->input('city');
        $hotels = $this->placeService->getHotelsByCityName($cityName);
        $user = $request->user('api');
        $this->placeService->annotateIsSavedForCollection($hotels, $user);
        $this->placeService->annotateWithGlobalTouristRank($hotels);
        return PlaceResource::collection($hotels);
    }
public function getTouristPlacesByCity(Request $request)
{
    $request->validate([
        'city' => 'required|string|exists:cities,name'
    ]);

    $cityName = $request->input('city');
    $places = $this->placeService->getTouristPlacesByCityName($cityName);

    $user = $request->user('api');
    $this->placeService->annotateIsSavedForCollection($places, $user);
    $this->placeService->annotateWithGlobalTouristRank($places);

    return PlaceResource::collection($places);
}

    private function handleImages($request, $place, $replace = false)
    {
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            if ($replace) {
                $this->placeService->replaceImages($images, $place);
            } else {
                $this->placeService->storeImages($images, $place);
            }
        }
    }

    public function getTopPlaces()
    {
        $data=[];
        try{
            $data=$this->placeService->getTopPlaces();
            return response()->json([
                "places"=>$data['places']??null,
                "message" =>$data['message']
            ], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
}
