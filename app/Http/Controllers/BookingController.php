<?php
namespace App\Http\Controllers;

use App\Http\Requests\ReserveRequest;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Throwable;

class BookingController extends Controller
{
    protected BookingService $bookingService;
    public function __construct(BookingService $bookingService) {
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
                "qr_code_url" => $data['qr_code_url'] ?? null,
            ], $data['code']);
        } catch (\Throwable $th) {
            return response()->json(["message" => $th->getMessage()], 500);
        }
    }
    public function myReservedTrips()
    {
        return response()->json($this->bookingService->myReservedTrips());
    }

    public function cancelReservation($bookingId)
    {
        $data = $this->bookingService->cancelReservation($bookingId);
        return response()->json(['message' => $data['message']], $data['code']);
    }
}
