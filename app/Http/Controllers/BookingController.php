<?php
namespace App\Http\Controllers;

use App\Http\Requests\ReserveRequest;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Throwable;

class BookingController extends Controller
{
    protected BookingService $bookingService;
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }
    public function reserve(ReserveRequest $request)
    {
        $data = [];
        try {
            $data = $this->bookingService->reserve($request->validated());
            return response()->json([
                "message" => $data['message'],
                "booking" => $data['booking'] ?? null,
            ], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function myBookings()
    {
        $data = [];
        try {
            $data = $this->bookingService->myBookings();
            return response()->json([
                "message" => $data['message'],
                "bookings" => $data['bookings'] ?? null,
            ], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
}
