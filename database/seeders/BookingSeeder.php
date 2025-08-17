<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\User;
use App\Models\Trip;
use App\Models\Event;
use App\Models\Passenger;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    public function run()
    {
        $trips = Trip::all();
        $events = Event::all();

        foreach (range(1, 30) as $i) {
            $type = collect(['trip', 'event', 'flight'])->random();

            $tickets = rand(1, 5); // نخزن عدد التذاكر
            $bookingData = [
                'user_id' => rand(3, 4),
                'number_of_tickets' => $tickets,
                'number_of_adults' => rand(1, $tickets),
                'number_of_children' => rand(0, $tickets),
                'number_of_infants' => rand(0, $tickets),
                'payment_status' => collect(['pending','paid','failed'])->random(),
                'is_paid' => rand(0, 1),
            ];

            if ($type === 'trip' && $trips->isNotEmpty()) {
                $trip = $trips->random();
                $bookingData['trip_id'] = $trip->id;
                $bookingData['price'] = $trip->price * $tickets;

            } elseif ($type === 'event' && $events->isNotEmpty()) {
                $event = $events->random();
                $bookingData['event_id'] = $event->id;
                $bookingData['price'] = $event->price * $tickets;

            } else {
                // flight booking
                $bookingData['flight_data'] = json_encode([
                    'from' => 'DXB',
                    'to' => 'CAI',
                    'airline' => collect(['Emirates', 'Qatar Airways', 'Turkish Airlines'])->random(),
                    'departure' => now()->addDays(rand(1, 30))->toDateTimeString(),
                    'arrival' => now()->addDays(rand(1, 30))->addHours(rand(2, 10))->toDateTimeString(),
                ]);
                $bookingData['flightOrderId'] = 'FL-' . strtoupper(Str::random(8));
                $bookingData['price'] = rand(100, 1000) * $tickets; // ممكن تعمل منطق خاص للـ flight
            }

            $booking = Booking::create($bookingData);

            // passengers equal number_of_tickets
            foreach (range(1, $booking->number_of_tickets) as $j) {
                Passenger::create([
                    'booking_id' => $booking->id,
                    'first_name' => fake()->firstName,
                    'last_name' => fake()->lastName,
                    'gender' => collect(['male', 'female'])->random(),
                    'birth_date' => fake()->dateTimeBetween('-60 years', '-2 years')->format('Y-m-d'),
                    'nationality' => fake()->country,
                    'email' => fake()->safeEmail,
                    'phone' => fake()->phoneNumber,
                    'country_code' => fake()->countryCode,
                    'passport_number' => strtoupper(Str::random(8)),
                    'passport_expiry_date' => now()->addYears(rand(1, 10))->format('Y-m-d'),
                ]);
            }
        }
    }
}
