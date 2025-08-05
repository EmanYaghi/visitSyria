<?php

namespace App\Services;

use Amadeus\Client;
use Amadeus\Exceptions\ResponseException;

class AmadeusService
{
    protected $amadeus;

    public function __construct()
    {
        $this->amadeus = \Amadeus\Client::builder(
            env('AMADEUS_CLIENT_ID'),
            env('AMADEUS_CLIENT_SECRET')
        )
        ->setHost(env('AMADEUS_ENV') === 'production'
            ? \Amadeus\Client::HOST_PRODUCTION
            : \Amadeus\Client::HOST_TEST)
        ->build();
    }

    public function searchFlights($origin, $destination, $departureDate, $adults = 1)
    {
        try {
            return $this->amadeus->getShopping()->getFlightOffers()
                ->get([
                    'originLocationCode' => $origin,
                    'destinationLocationCode' => $destination,
                    'departureDate' => $departureDate,
                    'adults' => $adults,
                    'max' => 5
                ]);
        } catch (ResponseException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function bookFlight($flightOffer, $travelerInfo)
    {
        try {
            return $this->amadeus->getBooking()->getFlightOrders()->post([
                'data' => [
                    'type' => 'flight-order',
                    'flightOffers' => [$flightOffer],
                    'travelers' => $travelerInfo
                ]
            ]);
        } catch (ResponseException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
