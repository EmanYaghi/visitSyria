<?php
namespace App\Http\Controllers;

use App\Http\Requests\PlaceStoreRequest;
use App\Http\Requests\PlaceUpdateRequest;
use App\Http\Resources\PlaceResource;
use App\Models\City;
use App\Services\PlaceService;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    protected $placeService;

    public function __construct(PlaceService $placeService)
    {
        $this->placeService = $placeService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['type', 'city_id']);
        $places = $this->placeService->getAll($filters);
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
    return PlaceResource::collection($places);
}

    public function store(PlaceStoreRequest $request)
    {
        $data = $request->validated();
        $data['city_id'] = City::where('name', $data['city_name'])->firstOrFail()->id;
        $place = $this->placeService->store($data);

        $this->handleImages($request, $place);

        return response()->json(['place' => new PlaceResource($place)],201);
    }

    public function show($id)
    {
        $place = $this->placeService->getById($id);
        return new PlaceResource($place);
    }

   public function update(PlaceUpdateRequest $request, $id)
    {
        $data = $request->validated();
        if (isset($data['city_name'])) {
            $data['city_id'] = City::where('name', $data['city_name'])->firstOrFail()->id;
        }

        $place = $this->placeService->update($id, $data);

        $this->handleImages($request, $place);

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
            return PlaceResource::collection($places);
        }

        public function getHotels(Request $request)
        {
            $places = $this->placeService->getHotels();
            return PlaceResource::collection($places);
        }

        public function getTouristPlaces(Request $request)
        {
            $places = $this->placeService->getTouristPlaces();
            return PlaceResource::collection($places);
        }
        public function getTopRatedTouristPlaces(Request $request)
        {
            $places = $this->placeService->getTopRatedTouristPlaces($request->all());
            return PlaceResource::collection($places);
        }
        public function getTouristPlacesByClassification($classification)
        {
            $places = $this->placeService->getTouristPlacesByClassification($classification);
            return PlaceResource::collection($places);
        }
        public function getRestaurantsByCity(Request $request)
    {
        $request->validate([
            'city' => 'required|string|exists:cities,name'
        ]);
        
        $cityName = $request->input('city');
        $restaurants = $this->placeService->getRestaurantsByCityName($cityName);
        
        return PlaceResource::collection($restaurants);
    }

    public function getHotelsByCity(Request $request)
    {
        $request->validate([
            'city' => 'required|string|exists:cities,name'
        ]);
        
        $cityName = $request->input('city');
        $hotels = $this->placeService->getHotelsByCityName($cityName);
        
        return PlaceResource::collection($hotels);
    }
    private function handleImages($request, $place)
    {
        if ($request->hasFile('images')) {
            $imageUrls = $this->placeService->storeImages($request->file('images'), $place);
            return $imageUrls;
        }
    }
}
