<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Media;
use App\Models\Event;

class EventSeeder extends Seeder
{
    public function run()
    {
        $events = [
            [
                'name' => 'مهرجان حلب السينمائي',
                'description' => 'مهرجان سنوي يعرض أفلامًا محلية وعالمية.',
                'longitude' => 37.2345,
                'latitude' => 37.2345,
                'place' => 'مسرح حلب',
                'date' => '2025-08-15',
                'duration_days' => 3,
                'duration_hours' => 8,
                'tickets' => 300,
                'price' => 1500.00,
                'event_type' => 'limited',
                'price_type' => 'paid',
                'pre_booking' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مهرجان دمشق الدولي',
                'description' => 'أحد أكبر مهرجانات الفنون في الشرق الأوسط.',
                'longitude' => 33.5138,
                'latitude' => 36.2765,
                'place' => 'مركز المعارض',
                'date' => '2025-09-05',
                'duration_days' => 7,
                'duration_hours' => 6,
                'tickets' => 0,
                'price' => 2000.00,
                'event_type' => 'unlimited',
                'price_type' => 'paid',
                'pre_booking' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'حفل موسيقي في اللاذقية',
                'description' => 'حفلة موسيقية للفنانين السوريين مع عرض ضوء مميز.',
                'longitude' => 35.5333,
                'latitude' => 35.7667,
                'place' => 'مسرح اللاذقية',
                'date' => '2025-10-01',
                'duration_days' => 1,
                'duration_hours' => 3,
                'tickets' => 150,
                'price' => 1000.00,
                'event_type' => 'limited',
                'price_type' => 'paid',
                'pre_booking' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($events as $eventData) {
            $event = Event::create($eventData);

            $mediaUrls = [];
            if ($eventData['event_type'] == 'limited') {
                $mediaUrls = ['http://127.0.0.1:8000/storage/events/limited_event.jpg'];
            } elseif ($eventData['event_type'] == 'unlimited') {
                $mediaUrls = ['http://127.0.0.1:8000/storage/events/unlimited_event.jpg'];
            }

            foreach ($mediaUrls as $url) {
                Media::create([
                    'event_id' => $event->id,
                    'url' => $url,
                ]);
            }
        }
    }
}
