<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\Media;
use App\Models\Tag;
use App\Models\TagName;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    public function run()
    {
        $postsData = [
            [
                'user_id' => 3,
                'description' => 'زيارة إلى قلعة حلب، إحدى أقدم القلاع في العالم، حيث التاريخ العريق والمشاهد البانورامية الخلابة.',
                'image' => 'https://upload.wikimedia.org/wikipedia/commons/5/5e/Aleppo_Citadel.jpg',
                'tags' => ['طرطوس', 'طبيعية', 'أثرية'],
            ],
            [
                'user_id' => 3,
                'description' => 'جولة في سوق الحميدية بدمشق، حيث الأصالة والعطور الشرقية والحرف اليدوية.',
                'image' => 'https://upload.wikimedia.org/wikipedia/commons/d/d0/Souq_Al-Hamidiyah_Damascus.jpg',
                'tags' => ['طرطوس', 'طبيعية', 'ترفيهي'],
            ],
            [
                'user_id' => 4,
                'description' => 'استكشاف آثار تدمر المهيبة، عاصمة مملكة الصحراء في العصور القديمة.',
                'image' => 'https://upload.wikimedia.org/wikipedia/commons/3/3b/Palmyra_ancient_ruins.jpg',
                'tags' => ['دمشق', 'طبيعية', 'أثرية'],
            ],
            [
                'user_id' => 4,
                'description' => 'رحلة إلى الساحل السوري في اللاذقية للاستمتاع بالشواطئ الجميلة والمأكولات البحرية الطازجة.',
                'image' => 'https://upload.wikimedia.org/wikipedia/commons/8/85/Latakia_beach.jpg',
                'tags' => ['دمشق', 'طبيعية', 'ساحلي'],
            ],
        ];

        DB::transaction(function () use ($postsData) {
            foreach ($postsData as $data) {
                $post = Post::create([
                    'user_id' => $data['user_id'],
                    'description' => $data['description'],
                    'status' => 'Approved',
                ]);

                Media::create([
                    'user_id' => $data['user_id'],
                    'post_id' => $post->id,
                    'url' => $data['image'],
                ]);

                if (!empty($data['tags']) && is_array($data['tags'])) {
                    foreach ($data['tags'] as $tagBody) {
                        $tagBodyTrim = trim((string) $tagBody);
                        if ($tagBodyTrim === '') {
                            continue;
                        }

                        $tagName = TagName::firstOrCreate(
                            ['body' => $tagBodyTrim, 'follow_to' => 'post']
                        );

                        Tag::create([
                            'post_id' => $post->id,
                            'tag_name_id' => $tagName->id,
                            'user_id' => $data['user_id'],
                        ]);
                    }
                }
            }
        });
    }
}
