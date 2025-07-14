<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CityService;
use Illuminate\Http\Request;

class CityController extends Controller
{
    protected $cityService;

    public function __construct(CityService $cityService)
    {
        $this->cityService = $cityService;
    }

    public function index()
    {
        $cities = $this->cityService->getAllCities();
        return response()->json($cities);
    }

    public function show($id)
    {
        $city = $this->cityService->getCityDetails($id);
        return response()->json($city);
    }
}