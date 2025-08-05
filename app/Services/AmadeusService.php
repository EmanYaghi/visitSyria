<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

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
        $response = Http::asForm()->post($this->baseUrl . '/v1/security/oauth2/token', [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        throw new \Exception('Unable to fetch Amadeus access token: ' . $response->body());
    }

    public function searchFlights(array $params)
    {
        $endpoint = $this->baseUrl . '/v2/shopping/flight-offers';

        $response = Http::withToken($this->accessToken)
            ->get($endpoint, [
                'originLocationCode'      => $params['origin'],
                'destinationLocationCode' => $params['destination'],
                'departureDate'           => $params['departure_date'],
                'returnDate'              => $params['return_date'] ?? null,
                'adults'                  => $params['adults'],
                'children'                => $params['children'] ?? 0,
                'infants'                 => $params['infants'] ?? 0,
                'travelClass'             => $params['travel_class'] ?? 'ECONOMY',
                'nonStop'                 => $params['non_stop'] ?? false,
                'max'                     => $params['max'] ?? 10,
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'error' => true,
            'message' => $response->json()['errors'][0]['detail'] ?? 'Something went wrong',
        ];
    }
}
>>>>>>> Stashed changes
