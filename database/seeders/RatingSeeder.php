<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rating;
use App\Models\Place;
use App\Models\User;

class RatingSeeder extends Seeder
{
    public function run()
    {
        // الحصول على المستخدمين المطلوبين
        $user1 = User::where('email', 'client@example.com')->first();
        $user2 = User::where('email', 'client2@example.com')->first();

        $places = Place::all();

        foreach ($places as $place) {
            $ratingValue = rand(1, 5);
            Rating::create([
                'user_id' => $user1->id,
                'place_id' => $place->id,
                'rating_value' => $ratingValue,
                'classification' => $ratingValue >= 3 ? 'positive' : 'negative',
            ]);
            $ratingValue = rand(1, 5);
            Rating::create([
                'user_id' => $user2->id,
                'place_id' => $place->id,
                'rating_value' => $ratingValue,
                'classification' => $ratingValue >= 3 ? 'positive' : 'negative',
            ]);
        }
    }
}