<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportStoreRequest;
use App\Http\Resources\SupportResource;
use App\Services\SupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SupportController extends Controller
{
    protected SupportService $service;

    public function __construct(SupportService $service)
    {
        $this->service = $service;
    }

    public function store(SupportStoreRequest $request)
    {
        $user = auth('api')->user() ?? auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validated();

        if (! $user->hasRole('super_admin')) {
            unset($data['category']);
        }

        if (! isset($data['category']) || ! in_array($data['category'], ['app','admin'], true)) {
            $data['category'] = $user->hasRole('admin') ? 'admin' : 'app';
        }

        $support = $this->service->createNote($user, $data);

        return response()->json([
            'id'      => $support->id,
            'rating'  => (string) $support->rating,
            'comment' => $support->comment,
        ], 201);
    }

    public function index(Request $request)
    {
        $user = auth('api')->user() ?? auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $acceptedSuperNames = ['super_admin', 'super-admin', 'superadmin'];

        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : [];

        $isSuper = false;
        foreach ($acceptedSuperNames as $name) {
            if (in_array($name, $roles, true) || (method_exists($user, 'hasRole') && $user->hasRole($name))) {
                $isSuper = true;
                break;
            }
        }

        if (! $isSuper) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $category = $request->query('category');

        if ($category) {
            $collection = $this->service->listSupportsForAdmin($category);
            return response()->json(SupportResource::collection($collection));
        }

        $grouped = $this->service->listSupportsForAdmin(null);
        return response()->json([
            'app' => SupportResource::collection(collect($grouped['app'] ?? [])),
            'admin' => SupportResource::collection(collect($grouped['admin'] ?? [])),
        ]);
    }

    public function monthlyRatings(Request $request)
    {
        $user = auth('api')->user() ?? auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $acceptedSuperNames = ['super_admin', 'super-admin', 'superadmin'];

        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : [];

        $isSuper = false;
        foreach ($acceptedSuperNames as $name) {
            if (in_array($name, $roles, true) || (method_exists($user, 'hasRole') && $user->hasRole($name))) {
                $isSuper = true;
                break;
            }
        }

        if (! $isSuper) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $year = $request->query('year') ? (int) $request->query('year') : null;
        $data = $this->service->monthlyRatingsCounts($year);
        $year = $year ?: date('Y');

        return response()->json([
            'year' => $year,
            'data' => $data
        ], 200);
    }
}
