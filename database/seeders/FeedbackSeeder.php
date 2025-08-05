<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comment;
use App\Models\Rating;
use App\Models\Place;

class FeedbackSeeder extends Seeder
{
    public function run()
    {
        $comments = [
            'كان المكان رائعاً والخدمة ممتازة.',
            'استمتعت كثيراً بالتجربة وسأعود مرة أخرى.',
            'الطعام لذيذ والأسعار مناسبة.',
            'الجو هادئ والموظفون ودودون.',
            'التنظيف يحتاج إلى تحسين بسيط.',
            'الموقع استراتيجي ولكن الانتظار طويل.',
            'بعض الأصناف كانت غير متوفرة عند الزيارة.',
        ];

        $classify = function (int $value) {
            return $value >= 3 ? 'positive' : 'negative';
        };
        $places = Place::orderBy('id')->take(14)->get();

        $userIds = [3, 4];

        foreach ($places as $place) {
            foreach ($userIds as $userId) {
                $ratingValue = rand(1, 5);
                $classification = $classify($ratingValue);

                $rating = Rating::create([
                    'user_id' => $userId,
                    'place_id' => $place->id,
                    'trip_id' => null,
                    'rating_value' => $ratingValue,
                    'classification' => $classification,
                ]);

                    $commentBody = $comments[array_rand($comments)];
                    Comment::create([
                        'user_id' => $userId,
                        'post_id' => null,
                        'place_id' => $place->id,
                        'support_id' => null,
                        'trip_id' => null,
                        'body' => $commentBody,
                    ]);
                }
            }
        }
    }

