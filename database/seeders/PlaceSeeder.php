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
                'city_id' => 1, // حلب
                'type' => 'restaurant',
                'name' => 'بيت حلب',
                'description' => 'أشهر مطاعم حلب يقدم المأكولات الحلبية الأصيلة.',
                'number_of_branches' => 3,
                'phone' => '933111111',
                'country_code' => '+963',
                'place' => 'سوق الجميلية',
                'longitude' => '37.1340',
                'latitude' => '36.2020',
                'rating' => 4.7,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 2, // دمشق
                'type' => 'restaurant',
                'name' => 'نوري',
                'description' => 'مطعم دمشقي تراثي في قلب المدينة القديمة.',
                'number_of_branches' => 1,
                'phone' => '933222222',
                'country_code' => '+963',
                'place' => 'باب شرقي',
                'longitude' => '36.3060',
                'latitude' => '33.5120',
                'rating' => 4.9,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 6, // اللاذقية
                'type' => 'restaurant',
                'name' => 'الكورنيش',
                'description' => 'مطعم بحري يطل على شاطئ اللاذقية.',
                'number_of_branches' => 2,
                'phone' => '933333333',
                'country_code' => '+963',
                'place' => 'الكورنيش الجنوبي',
                'longitude' => '35.7800',
                'latitude' => '35.5200',
                'rating' => 4.5,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 5, // حمص
                'type' => 'restaurant',
                'name' => 'الفاخر',
                'description' => 'مطعم فاخر يقدم أشهى المأكولات الشرقية.',
                'number_of_branches' => 1,
                'phone' => '933444444',
                'country_code' => '+963',
                'place' => 'شارع القوتلي',
                'longitude' => '36.7160',
                'latitude' => '34.7320',
                'rating' => 4.3,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 7, // طرطوس
                'type' => 'restaurant',
                'name' => 'البحر',
                'description' => 'مطعم مأكولات بحرية طازجة على شاطئ طرطوس.',
                'number_of_branches' => 1,
                'phone' => '933555555',
                'country_code' => '+963',
                'place' => 'الكورنيش البحري',
                'longitude' => '35.8800',
                'latitude' => '34.8900',
                'rating' => 4.6,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'city_id' => 2, // دمشق
                'type' => 'hotel',
                'name' => 'فندق الشام',
                'description' => 'فندق 5 نجوم في قلب العاصمة الدمشقية.',
                'number_of_branches' => 1,
                'phone' => '944111111',
                'country_code' => '+963',
                'place' => 'أبو رمانة',
                'longitude' => '36.2920',
                'latitude' => '33.5180',
                'rating' => 4.8,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 1, // حلب
                'type' => 'hotel',
                'name' => 'فندق حلب الدولي',
                'description' => 'فندق فاخر قرب قلعة حلب الأثرية.',
                'number_of_branches' => 1,
                'phone' => '944222222',
                'country_code' => '+963',
                'place' => 'ساحة سعد الله الجابري',
                'longitude' => '37.1580',
                'latitude' => '36.2120',
                'rating' => 4.6,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 6, // اللاذقية
                'type' => 'hotel',
                'name' => 'فندق اللاذقية الكبير',
                'description' => 'فندق 4 نجوم يطل على البحر المتوسط.',
                'number_of_branches' => 1,
                'phone' => '944333333',
                'country_code' => '+963',
                'place' => 'الكورنيش الشمالي',
                'longitude' => '35.7850',
                'latitude' => '35.5300',
                'rating' => 4.4,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 5, // حمص
                'type' => 'hotel',
                'name' => 'فندق حمص السياحي',
                'description' => 'فندق وسط المدينة مع إطلالة بانورامية.',
                'number_of_branches' => 1,
                'phone' => '944444444',
                'country_code' => '+963',
                'place' => 'شارع الحكيم',
                'longitude' => '36.7220',
                'latitude' => '34.7420',
                'rating' => 4.2,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 14, // الحسكة
                'type' => 'hotel',
                'name' => 'فندق الحسكة الجديد',
                'description' => 'أحدث فنادق المنطقة مع خدمات مميزة.',
                'number_of_branches' => 1,
                'phone' => '944555555',
                'country_code' => '+963',
                'place' => 'المنطقة السياحية',
                'longitude' => '40.7500',
                'latitude' => '36.5000',
                'rating' => 4.0,
                'classification' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'city_id' => 2, // دمشق
                'type' => 'tourist',
                'name' => 'الجامع الأموي',
                'description' => 'أحد أقدم وأجمل المساجد في العالم الإسلامي.',
                'number_of_branches' => 1,
                'phone' => null,
                'country_code' => null,
                'place' => 'المركز القديم',
                'longitude' => '36.3060',
                'latitude' => '33.5120',
                'rating' => 5.0,
                'classification' => 'أثرية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 1, // حلب
                'type' => 'tourist',
                'name' => 'قلعة حلب',
                'description' => 'إحدى أكبر وأقدم القلاع في العالم.',
                'number_of_branches' => 1,
                'phone' => null,
                'country_code' => null,
                'place' => 'مركز المدينة القديمة',
                'longitude' => '37.1610',
                'latitude' => '36.1990',
                'rating' => 4.9,
                'classification' => 'أثرية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 4, // حماة
                'type' => 'tourist',
                'name' => 'نواعير حماة',
                'description' => 'أكبر النواعير في العالم وآثار تاريخية.',
                'number_of_branches' => 1,
                'phone' => null,
                'country_code' => null,
                'place' => 'جانب نهر العاصي',
                'longitude' => '36.7560',
                'latitude' => '35.1360',
                'rating' => 4.7,
                'classification' => 'طبيعية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 5, // حمص
                'type' => 'tourist',
                'name' => 'آثار تدمر',
                'description' => 'مدينة أثرية تعود للحضارة الرومانية.',
                'number_of_branches' => 1,
                'phone' => null,
                'country_code' => null,
                'place' => 'صحراء حمص',
                'longitude' => '38.2739',
                'latitude' => '34.5560',
                'rating' => 4.8,
                'classification' => 'أثرية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'city_id' => 7, // طرطوس
                'type' => 'tourist',
                'name' => 'قلعة المرقب',
                'description' => 'قلعة صليبية تاريخية تطل على البحر.',
                'number_of_branches' => 1,
                'phone' => null,
                'country_code' => null,
                'place' => 'جبل قرب الساحل',
                'longitude' => '35.9500',
                'latitude' => '35.1500',
                'rating' => 4.5,
                'classification' => 'أثرية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($places as $placeData) {
            $place = Place::create($placeData);
            
                    $mediaUrls = [];
            if ($placeData['type'] == 'restaurant') {
                $mediaUrls = ['places/restaurant1.jpg'];
            } elseif ($placeData['type'] == 'hotel') {
                $mediaUrls = ['places/hotel1.jpg'];
            } elseif ($placeData['type'] == 'tourist') {
                $mediaUrls = ['places/tourist1.jpg'];
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