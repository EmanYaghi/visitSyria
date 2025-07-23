<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
        // إرجاع قائمة الأماكن بناءً على الفلاتر
        $places = $this->placeService->getAll($request->only(['type', 'city_id']));
        return PlaceResource::collection($places);
    }

public function store(PlaceStoreRequest $request)
    {
        $data = $request->validated();

        // جلب المدينة بناءً على اسم المدينة
        $city = City::where('name', $data['city_name'])->first();

        if (!$city) {
            return response()->json(['error' => 'City not found'], 404);
        }

        // إضافة city_id إلى البيانات المدخلة
        $data['city_id'] = $city->id;

        // معالجة الصور
        if ($request->hasFile('images')) {
            $data['images'] = $this->handleImages($request->file('images'));
        }

        // تخزين المكان عبر الخدمة
        $place = $this->placeService->store($data);
        return new PlaceResource($place);
    }

    public function show($id)
    {
        $place = $this->placeService->getById($id);
        return new PlaceResource($place);
    }

    public function update(PlaceUpdateRequest $request, $id)
    {
        $place = $this->placeService->update($id, $request->validated());
        return new PlaceResource($place);
    }

    public function destroy($id)
    {
        $this->placeService->delete($id);
        return response()->json(['message' => 'Deleted successfully.']);
    }

    private function handleImages($images)
    {
        $imagePaths = [];
        foreach ($images as $image) {
            $path = $image->store('images', 'public');
            $imagePaths[] = $path;
        }
        return $imagePaths;
    }
}
