<?php

namespace App\Http\Controllers;

use App\Services\TripService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    protected TripService $tripService;
    public function __construct(TripService $tripService) {
        $this->tripService = $tripService;
    }
    public function index()
    {

    }
    public function store(Request $request)
    {

    }
    public function show($id)
    {

    }
    public function update(Request $request, $id)
    {

    }
    public function destroy( $id)
    {

    }
    public function companyTrips( $id)
    {

    }
}
