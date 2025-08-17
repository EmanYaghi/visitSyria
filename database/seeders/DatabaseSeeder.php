<?php

namespace Database\Seeders;

use App\Models\Support;
use App\Models\Tag;
use App\Models\TagName;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionsSeeder::class,
            CitiesTableSeeder::class,
            MediaTableSeeder::class,
            TagNameSeeder::class,
            PlaceSeeder::class,
            EventSeeder::class,
            FeedbackSeeder::class,
            ArticlesSeeder::class,
            SettingSeeder::class,
            PostSeeder::class,
            BookingSeeder::class,
            SupportSeeder::class,

        ]);
        Trip::factory()->count(10)->create()->each(function ($trip) {
            $tagNameIds = TagName::where('follow_to','trip')->inRandomOrder()->take(rand(1, 7))->pluck('id');
            foreach ($tagNameIds as $tagNameId) {
                Tag::create([
                    'trip_id' => $trip->id,
                    'tag_name_id' => $tagNameId,
                ]);
            }

            for ($i = 0; $i < rand(1,4); $i++) {
                $trip->media()->create([
                    'url' =>  'kjmnjgfhgdvh'.rand(1, 30) . '.jpg',
                ]);
            }

            for ($i = 1; $i < rand(1, 5); $i++) {
                $timeline=$trip->timelines()->create([
                    'day_number'=>$i
                ]);
                for($j=1;$j<rand(1,3);$j++){
                    $timeline->sections()->create([
                        'time'=>'9:00',
                        'title'=>"bsjdh",
                        'description'=>["ksjdcj hdhfgc kvh udycf","hcj jdhcg kfh","jcd chg jvgu"],
                        'latitude'=>49.4146100,
                        'longitude'=>8.6814950

                    ]);
                }
            }
            for ($i = 0; $i < rand(1,4); $i++) {
                $trip->comments()->create([
                    'user_id' => rand(3, 4),
                    'body'=>"very nice "
                ]);
            }
            for ($i = 0; $i < rand(1, 4); $i++) {
                $ratingValue = rand(1, 5);
                $trip->ratings()->create([
                    'user_id' => rand(3, 4),
                    'rating_value' => $ratingValue,
                    'classification' => $ratingValue >= 3 ? 'positive' : 'negative',
                ]);
            }

            for ($i = 0; $i < rand(0, 2); $i++) {
                $trip->saves()->create([
                    'user_id' => rand(3, 4),
                ]);
            }
        });
    }
}
