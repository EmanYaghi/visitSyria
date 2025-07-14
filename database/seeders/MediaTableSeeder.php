<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\City;
use Illuminate\Database\Seeder;

class MediaTableSeeder extends Seeder
{
    public function run()
    {
        $cities = City::all();

        $images = [
            'حلب' => [
                'aleppo.jpg'
            ],
            'دمشق' => [
                'damascus.jpg',
            ],
            'ريف دمشق' => [
                'rural_damascus.jpg'
            ],
            'حماة' => [
                'hama.jpg'
            ],
            'حمص' => [
                'homs.jpg'
            ],
            'اللاذقية' => [
                'latakia.jpg'
            ],
            'طرطوس' => [
                'tartus.jpg'
            ],
            'درعا' => [
                'daraa.jpg'
            ],
            'القنيطرة' => [
                'quneitra.jpg'
            ],
            'السويداء' => [
                'sweida.jpg'
            ],
            'الرقة' => [
                'raqqa.jpg'
            ],
            'دير الزور' => [
                'deir_ezzor.jpg'
            ],
            'الحسكة' => [
                'hasakah.jpg'
            ],
            'ادلب' => [
                'edleb.jpg'
            ],
        ];

        foreach ($cities as $city) {
            if (array_key_exists($city->name, $images)) {
                $cityImages = $images[$city->name];

                foreach ($cityImages as $imageName) {
                    Media::create([
                        'url' => 'storage/cities/' . $imageName,
                        'city_id' => $city->id,
                        'user_id' => null,
                        'post_id' => null,
                        'place_id' => null,
                        'event_id' => null,
                    ]);
                }
            }
        }
    }
}
