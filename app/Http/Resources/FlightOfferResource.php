<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class FlightOfferResource extends JsonResource
{
    protected array $carriers;
    protected array $locations;
    protected ?string $travelClass;

    protected array $airportNamesArabic = [
        'DAM' => 'مطار دمشق الدولي',
        'AMM' => 'مطار الملكة علياء الدولي',
        'DXB' => 'مطار دبي الدولي',
        'BEY' => 'مطار بيروت الدولي',
        'LHR' => 'مطار هيثرو الدولي',
        'CDG' => 'مطار شارل ديغول الدولي',
        'JFK' => 'مطار جون إف كينيدي الدولي',
        'LAX' => 'مطار لوس أنجلوس الدولي',
        'ORD' => 'مطار أوهير الدولي',
        'HND' => 'مطار هانيدا الدولي',
        'NRT' => 'مطار ناريتا الدولي',
        'IST' => 'مطار إسطنبول',
        'FRA' => 'مطار فرانكفورت',
        'MUC' => 'مطار ميونخ',
        'DOH' => 'مطار حمد الدولي',
        'AUH' => 'مطار أبوظبي الدولي',
        'BKK' => 'مطار سوفارنابومي',
        'SIN' => 'مطار شانغي',
        'KUL' => 'مطار كوالالمبور الدولي',
        'SYD' => 'مطار سيدني',
        'MEL' => 'مطار ملبورن',
        'YYZ' => 'مطار تورونتو بيرسون الدولي',
        'YVR' => 'مطار فانكوفر الدولي',
        'GRU' => 'مطار غواروليوس الدولي',
        'EZE' => 'مطار إزيزا الدولي',
        'CPT' => 'مطار كيب تاون الدولي',
        'JNB' => 'مطار أو آر تامبو الدولي',
        'MAD' => 'مطار مدريد باراخاس',
        'BCN' => 'مطار برشلونة',
        'FCO' => 'مطار ليوناردو دافنشي روما',
        'MXP' => 'مطار مالبينسا ميلانو',
        'AMS' => 'مطار شيفول أمستردام',
        'BRU' => 'مطار بروكسل',
        'ZRH' => 'مطار زيورخ',
        'GVA' => 'مطار جنيف',
        'ATH' => 'مطار أثينا الدولي',
        'CUN' => 'مطار كانكون الدولي',
        'MEX' => 'مطار مكسيكو سيتي',
        'ICN' => 'مطار إنتشون الدولي',
        'HKG' => 'مطار هونغ كونغ الدولي',
        'SVO' => 'مطار شيريميتيفو الدولي',
        'DME' => 'مطار دوموديدوفو',
        'LIS' => 'مطار لشبونة',
        'OSL' => 'مطار أوسلو',
        'ARN' => 'مطار ستوكهولم أرلاندا',
        'CPH' => 'مطار كوبنهاغن',
        'HEL' => 'مطار هلسنكي',
        'DUB' => 'مطار دبلن',
    ];

    public function __construct($resource, array $carriers = [], array $locations = [], ?string $travelClass = null)
    {
        parent::__construct($resource);
        $this->carriers    = $carriers;
        $this->locations   = $locations;
        $this->travelClass = $travelClass;
    }

    /**
     * Build a structured representation for a single itinerary (used for outbound/inbound).
     */
    protected function buildItinerary(array $itinerary, $request)
    {
        $segments = $itinerary['segments'] ?? [];
        $first = $segments[0] ?? null;
        $last  = end($segments);

        $departureDT = isset($first['departure']['at']) ? Carbon::parse($first['departure']['at']) : null;
        $arrivalDT   = isset($last['arrival']['at']) ? Carbon::parse($last['arrival']['at']) : null;

        $originCode = $first['departure']['iataCode'] ?? null;
        $destCode   = $last['arrival']['iataCode'] ?? null;

        $segmentsDetails = [];
        foreach ($segments as $index => $segment) {
            $depTime = Carbon::parse($segment['departure']['at']);
            $arrTime = Carbon::parse($segment['arrival']['at']);

            $segOriginCode = $segment['departure']['iataCode'] ?? null;
            $segDestCode   = $segment['arrival']['iataCode'] ?? null;

            $segInfo = [
                'segment_number' => $index + 1,
                'origin_airport_code' => $segOriginCode,
                'origin_airport_name' => $this->getAirportName($segOriginCode),
                'destination_airport_code' => $segDestCode,
                'destination_airport_name' => $this->getAirportName($segDestCode),
                'departure_time' => $depTime->format('Y-m-d H:i'),
                'arrival_time'   => $arrTime->format('Y-m-d H:i'),
                'duration_hours' => $this->convertDurationToHours($segment['duration'] ?? null),
                'airline'        => $this->carriers[$segment['carrierCode']] ?? $segment['carrierCode'],
            ];

            if (isset($segments[$index + 1])) {
                $nextDepTime = Carbon::parse($segments[$index + 1]['departure']['at']);
                $layoverHours = (int) floor($arrTime->diffInMinutes($nextDepTime) / 60);
                $segInfo['layover_hours'] = $layoverHours;
            }

            $segmentsDetails[] = $segInfo;
        }

        return [
            'origin_airport_code' => $originCode,
            'origin_airport_name' => $this->getAirportName($originCode),
            'destination_airport_code' => $destCode,
            'destination_airport_name' => $this->getAirportName($destCode),
            'departure_date' => $departureDT?->format('Y-m-d'),
            'departure_time' => $departureDT?->format('H:i'),
            'arrival_date'   => $arrivalDT?->format('Y-m-d'),
            'arrival_time'   => $arrivalDT?->format('H:i'),
            'departure_datetime_iso' => $departureDT?->toIso8601String(),
            'departure_timestamp'    => $departureDT?->timestamp,
            'duration_hours' => $this->convertDurationToHours($itinerary['duration'] ?? null),
            'stops' => max(count($segments) - 1, 0),
            'segments' => $segmentsDetails,
            'airline'  => $this->carriers[$first['carrierCode']] ?? ($first['carrierCode'] ?? null),
        ];
    }

    public function toArray($request)
    {
        $priceTotal = isset($this->resource['price']['total']) ? (float)$this->resource['price']['total'] : null;
        $currency   = $this->resource['price']['currency'] ?? null;
        $travelerCount = isset($this->resource['travelerPricings']) ? count($this->resource['travelerPricings']) : 0;
        $pricePerPassenger = ($priceTotal && $travelerCount > 0) ? round($priceTotal / $travelerCount, 2) : null;
        $seatsRemaining = $this->resource['numberOfBookableSeats'] ?? null;

        $itineraries = $this->resource['itineraries'] ?? [];

        // If there are two or more itineraries -> treat as a round-trip (outbound + inbound)
        if (count($itineraries) >= 2) {
            $out = $this->buildItinerary($itineraries[0], $request);
            $in  = $this->buildItinerary($itineraries[1], $request);

            return [
                'id' => $this->resource['id'] ?? null,
                'is_round_trip' => true,
                'price_total' => $priceTotal,
                'currency'    => $currency,
                'traveler_count' => $travelerCount,
                'price_per_passenger' => $pricePerPassenger,
                'seats_remaining' => $seatsRemaining,
                'outbound' => $out,
                'inbound'  => $in,
                // convenience top-level timestamps for sorting/timeline
                'departure_timestamp' => $out['departure_timestamp'] ?? null,
                'return_timestamp'    => $in['departure_timestamp'] ?? null,
            ];
        }

        // Otherwise single-itinerary (one-way)
        $singleItinerary = $itineraries[0] ?? null;
        if (!$singleItinerary) {
            // fallback: return minimal data
            return [
                'id' => $this->resource['id'] ?? null,
                'is_round_trip' => false,
                'price_total' => $priceTotal,
                'currency' => $currency,
            ];
        }

        // reuse buildItinerary for single
        $built = $this->buildItinerary($singleItinerary, $request);
        return array_merge([
            'id' => $this->resource['id'] ?? null,
            'is_round_trip' => false,
            'price_total' => $priceTotal,
            'currency' => $currency,
            'traveler_count' => $travelerCount,
            'price_per_passenger' => $pricePerPassenger,
            'seats_remaining' => $seatsRemaining,
        ], $built);
    }

    protected function getAirportName($code)
    {
        if (!$code) return null;

        if (isset($this->airportNamesArabic[$code])) {
            return $this->airportNamesArabic[$code];
        }

        if (isset($this->locations[$code]['airportName'])) {
            return $this->locations[$code]['airportName'];
        }

        return $code;
    }

    private function convertDurationToHours(?string $duration): ?int
    {
        if (!$duration) return null;
        preg_match('/PT(\d+H)?(\d+M)?/', $duration, $matches);
        $hours = isset($matches[1]) ? (int) rtrim($matches[1], 'H') : 0;
        $minutes = isset($matches[2]) ? (int) rtrim($matches[2], 'M') : 0;
        if ($minutes >= 30) $hours += 1;
        return $hours;
    }
}
