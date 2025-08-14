<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use App\Http\Requests\StoreSettingRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected SettingService $service;

    public function __construct(SettingService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $type = $request->query('type');
        $categoryFilter = $request->query('category'); // optional
        $limit = $request->query('limit');

        if ($type !== null) {
            // إذا طُلب النوع، نرجع العدّ أولاً ثم البيانات (بالفلتر إن وُجد)
            $counts = $this->service->countByType($type);

            $settings = $this->service->findByType($type, $categoryFilter);

            if ($limit !== null && is_numeric($limit)) {
                $settings = $settings->take((int) $limit);
            }

            return response()->json([
                'counts' => $counts,
                'data' => SettingResource::collection($settings),
            ], 200);
        }

        // السلوك الافتراضي: رجْع كل الإعدادات (مع دعم limit)
        $all = $this->service->getAll();

        if ($limit !== null && is_numeric($limit)) {
            $all = $all->take((int) $limit);
        }

        return SettingResource::collection($all);
    }

    public function show(Setting $setting)
    {
        return new SettingResource($setting);
    }

    public function store(StoreSettingRequest $request)
    {
        $setting = $this->service->create($request->validated());
        return (new SettingResource($setting))->response()->setStatusCode(201);
    }

    public function update(UpdateSettingRequest $request, Setting $setting)
    {
        $updated = $this->service->update($setting, $request->validated());
        return new SettingResource($updated);
    }

    public function destroy($id)
    {
        $setting = $this->service->find($id);
        $this->service->delete($setting);

        return response()->json([
            'message' => 'Deleted successfully'
        ], 200);
    }

    public function getByType(Request $request, $type)
    {
        $category = $request->query('category'); // optional
        $settings = $this->service->findByType($type, $category);

        $counts = $this->service->countByType($type);

        return response()->json([
            'counts' => $counts,
            'data' => SettingResource::collection($settings),
        ], 200);
    }

    public function getByCategory($category)
    {
        $settings = $this->service->getByCategory($category);
        return SettingResource::collection($settings);
    }

    public function upsertByType(Request $request, $type)
    {
        $data = $request->only(['title', 'description', 'category']);

        if (empty($data['category']) || !in_array($data['category'], ['app', 'admin'])) {
            return response()->json(['message' => 'category is required and must be one of: app, admin'], 422);
        }

        $setting = $this->service->upsertByType($type, $data['category'], $data);
        return new SettingResource($setting);
    }
}
