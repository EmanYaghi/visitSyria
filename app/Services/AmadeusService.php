<?php
namespace App\Services;

use App\Models\Booking;
use Google\Service\ServiceControl\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AmadeusService
{
    protected $clientId;
    protected $clientSecret;
    protected $baseUrl;
    protected $accessToken;

    public function __construct()
    {
        $this->clientId     = env('AMADEUS_CLIENT_ID');
        $this->clientSecret = env('AMADEUS_CLIENT_SECRET');
        $this->baseUrl      = env('AMADEUS_BASE_URL', 'https://test.api.amadeus.com');
        $this->accessToken  = $this->getAccessToken();
    }

    protected function getAccessToken()
    {
        $cacheKey = 'amadeus_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::asForm()->post($this->baseUrl . '/v1/security/oauth2/token', [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            $json = $response->json();
            $token = $json['access_token'] ?? null;
            $expiresIn = isset($json['expires_in']) ? (int) $json['expires_in'] : 300;

            Cache::put($cacheKey, $token, max($expiresIn - 60, 60));

            return $token;
        }

        throw new \Exception('Unable to fetch Amadeus access token: ' . $response->body());
    }

    public function searchFlights(array $params)
    {
        $endpoint = $this->baseUrl . '/v2/shopping/flight-offers';

        $query = [
            'originLocationCode'      => $params['originLocationCode'] ?? null,
            'destinationLocationCode' => $params['destinationLocationCode'] ?? null,
            'departureDate'           => $params['departureDate'] ?? null,
            'returnDate'              => $params['returnDate'] ?? null,
            'adults'                  => $params['adults'] ?? 1,
            'children'                => $params['children'] ?? 0,
            'infants'                 => $params['infants'] ?? 0,
            'travelClass'             => $params['travelClass'] ?? 'ECONOMY',
            'nonStop'                 => array_key_exists('nonStop', $params) ? ($params['nonStop'] ? 'true' : 'false') : null,
            'max'                     => $params['max'] ?? 10,
            'currencyCode'            => $params['currencyCode'] ?? 'USD',
        ];

        $query = array_filter($query, function ($v) {
            return $v !== null;
        });

        $response = Http::withToken($this->accessToken)
            ->get($endpoint, $query);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'error' => true,
            'message' => $response->json()['errors'][0]['detail'] ?? 'Something went wrong',
        ];
    }
    public function searchLocation(string $keyword)
    {
        $endpoint = $this->baseUrl . '/v1/reference-data/locations';

        $response = Http::withToken($this->accessToken)
            ->get($endpoint, [
                'subType' => 'AIRPORT,CITY',
                'keyword' => $keyword,
                'page[limit]' => 10,
                'sort' => 'analytics.travelers.score',
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'error' => true,
            'message' => $response->json()['errors'][0]['detail'] ?? 'Something went wrong',
        ];
    }

    public function bookFlight($request)
    {
        $user = Auth::user();
        $request['is_paid']=false;
        $request['number_of_tickets']=$request['number_of_adults']+$request['number_of_children']+$request['number_of_infants'];
        $bookings = Booking::where('user_id', $user->id)->get();
        foreach ($bookings as $booking) {
            if ($booking->flight_data == $request['flight_data']) {
                return [
                    'message' => "you already reserve this flight",
                    'code'    => 400
            ];
        }}

        $booking=$user->bookings()->create($request);
        foreach($request['passengers'] as $passenger)
            $booking->passengers()->create($passenger);
        $booking->price=$booking->flight_data['price_total'];
        $booking->save();
        return [
            'message' => 'please pay to confirm bookings',
            'code' => 201,
            'booking' => [
                'id'=>$booking->id,
                'price'=>$booking->price,
            ],
        ];
    }
    public function confirmBookFlight(Booking $booking,$passengers)
    {
        $endpoint = $this->baseUrl . '/v1/booking/flight-orders';
        $response = Http::withToken($this->accessToken)
            ->post($endpoint, [
                "data" => [
                    "type" => "flight-order",
                    "flightOffers" => $booking->flightOffer,
                    "travelers" => $passengers,
                ]
            ]);
        if ($response->successful()) {
            return $response->json();
        }
        return [
            'error' => true,
            'message' => $response->json()['errors'][0]['detail'] ?? 'Booking failed',
        ];
    }
    public function cancelBooking(Booking $booking)
    {
        $flightOrderId=$booking->flightOrderId;
        $endpoint = $this->baseUrl . '/v1/booking/flight-orders/' . $flightOrderId;
        $response = Http::withToken($this->accessToken)->delete($endpoint);
        if ($response->successful()) {
            return [
                'error' => false,
                'message' => 'Booking cancelled successfully',
                'data' => $response->json()
            ];
        }
        return [
            'error' => true,
            'message' => $response->json()['errors'][0]['detail'] ?? 'Cancellation failed'
        ];
    }
}
