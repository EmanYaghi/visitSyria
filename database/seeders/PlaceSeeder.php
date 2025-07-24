<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Media;
use App\Models\Place;

class PlaceSeeder extends Seeder
{
    public function run()
    {
        $places = [
            [
                'city_id' => 1,
                'type' => 'restaurant',
                'name' => 'ام شريف',
                'description' => 'مطعم شرقي راقٍ يقدم المأكولات الأصيلة.',
                'number_of_branches' => 2,
                'phone' => '933809100',
                'country_code' => '+963',
                'place' => 'شارع شكري القوتلي',
                'longitude' => '36.3090',
                'latitude' => '33.5080',
                'rating' => 4.5,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 1,
                'type' => 'hotel',
                'name' => 'فندق ارميتاج',
                'description' => 'فندق خمس نجوم يوفر خدمات فاخرة.',
                'number_of_branches' => 1,
                'phone' => '933809200',
                'country_code' => '+963',
                'place' => 'ساحة عرنوس',
                'longitude' => '36.3110',
                'latitude' => '33.5090',
                'rating' => 4.8,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 2,
                'type' => 'tourist',
                'name' => 'باب شرقي',
                'description' => 'موقع أثري قديم ذو تاريخ طويل.',
                'number_of_branches' => 1,
                'phone' => null,
                'country_code' => null,
                'place' => 'الجهة الشرقية لمدينة دمشق',
                'longitude' => '37.1234',
                'latitude' => '34.5678',
                'rating' => 5.0,
                'classification' => 'أثرية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($places as $placeData) {
            $place = Place::create($placeData);
            
            $mediaUrls = [];
            if ($placeData['type'] == 'restaurant') {
                $mediaUrls = ['http://127.0.0.1:8000/storage/places/restaurant1.jpg'];
            } elseif ($placeData['type'] == 'hotel') {
                $mediaUrls = ['http://127.0.0.1:8000/storage/places/hotel1.jpg'];
            } elseif ($placeData['type'] == 'tourist') {
                $mediaUrls = ['http://127.0.0.1:8000/storage/places/tourist1.jpg'];
            }

            foreach ($mediaUrls as $url) {
                Media::create([
                    'place_id' => $place->id,
                    'url' => $url,
                ]);
            }
    }
}
}
