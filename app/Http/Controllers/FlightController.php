<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AmadeusService;

class FlightController extends Controller
{
    protected $amadeus;
    public function __construct(AmadeusService $amadeus) {
        $this->amadeus = $amadeus;
    }

    // من سوريا إلى الخارج
    public function fromSyria(Request $request) {
        $destination = $request->query('destination');    // مثل "LON"
        $date        = $request->query('departureDate');  // تاريخ الإقلاع YYYY-MM-DD
        $flights = $this->amadeus->searchFlights('DAM', $destination, $date);
        return response()->json($flights);
    }

    // من الخارج إلى سوريا
    public function toSyria(Request $request) {
        $origin = $request->query('origin');              // مثل "IST"
        $date   = $request->query('departureDate');
        $flights = $this->amadeus->searchFlights($origin, 'DAM', $date);
        return response()->json($flights);
    }

    // من وإلى سوريا (كل الاتجاهات)
    public function syriaAll(Request $request) {
        $origin      = $request->query('origin'); 
        $destination = $request->query('destination');
        $date        = $request->query('departureDate');
        $results = [];
        // رحلات من سوريا
        if ($destination) {
            $results = array_merge(
                $results,
                $this->amadeus->searchFlights('DAM', $destination, $date)
            );
        }
        // رحلات إلى سوريا
        if ($origin) {
            $results = array_merge(
                $results,
                $this->amadeus->searchFlights($origin, 'DAM', $date)
            );
        }
        return response()->json($results);
    }
}
