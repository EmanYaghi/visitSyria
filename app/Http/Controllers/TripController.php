<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trip\CreateTripRequest;
use App\Http\Requests\Trip\UpdateTripRequest;
use App\Services\TripService;
use Illuminate\Http\Request;
use Throwable;

class TripController extends Controller
{
    protected TripService $tripService;
    public function __construct(TripService $tripService) {
        $this->tripService = $tripService;
    }
    public function index()
    {
        $data=[];
        try{
            $data=$this->tripService->index();
            return response()->json(["message"=>$data['message'],"trips"=>$data['trips']],$data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
        $data=$this->tripService->index();
        return response()->json(["trips"=>$data['trips'],"message" =>$data['message']], $data['code']);
    }
    public function store(CreateTripRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['images'] = $request->file('images'); // THIS LINE IS CRITICAL

        $data = [];
        try {
            $data = $this->tripService->store($validatedData);
            return response()->json(["trip"=>$data['trip'],"message" => $data['message']], $data['code']);
        } catch (Throwable $th) {
            return response()->json(["message" => $th->getMessage()]);
        }
    }
    public function show($id)
    {
        $data=[];
        try{
            $data=$this->tripService->show($id);
            return response()->json(["trip"=>$data['trip'],"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function update(UpdateTripRequest $request, $id)
    {
         $data=[];
        try{
            $data=$this->tripService->update($request->validated(),$id);
            return response()->json(["message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function destroy( $id)
    {
         $data=[];
        try{
            $data=$this->tripService->destroy($id);
            return response()->json(["message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function companyTrips( $id)
    {
         $data=[];
        try{
            $data=$this->tripService->companyTrips($id);
            return response()->json(["trips"=>$data['trips'],"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function offers()
    {
        $data=[];
        try{
            $data=$this->tripService->offers();
            return response()->json(["trips"=>$data['trips'],"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function similarTrips($id)
    {
        $data=[];
        try{
            $data=$this->tripService->similarTrips($id);
            return response()->json(["trips"=>$data['trips'],"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
}
