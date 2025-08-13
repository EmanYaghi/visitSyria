<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Article;
use App\Models\TagName;

class ArticlesSeeder extends Seeder
{
    public function run()
    {
        Storage::disk('public')->makeDirectory('articles');

        foreach (TagName::$article as $name) {
            TagName::firstOrCreate(
                ['body' => $name, 'follow_to' => 'article'],
                []
            );
        }

        $articlesData = [
            [
                'title' => 'لمحة عن دمشق القديمة',
                'body'  => 'دمشق مدينة عريقة تجمع تاريخاً طويلاً بين الأزقة والأبنية الأثرية. زيارة ساحة الأمويين والسوق القديم تكشف عن روح المدينة النابضة.',
                'tags'  => ['دمشق','تاريخية','أثرية'],
            ],
            [
                'title' => 'حلب: بين الأسواق والقلعة',
                'body'  => 'حلب، بقلعتها وأسواقها التاريخية، مرآة للتبادل الثقافي والتجاري عبر القرون. نكهة المدينة تتجلى في أطباقها وأسواقها القديمة.',
                'tags'  => ['حلب','تاريخية','ثقافية'],
            ],
            [
                'title' => 'سواحل طرطوس واللاذقية',
                'body'  => 'سواحل البحر المتوسط عند طرطوس واللاذقية تقدم شواطئ هادئة ومناظر طبيعية ملهمة للهدوء والراحة بعيداً عن صخب المدن.',
                'tags'  => ['طرطوس','اللاذقية','طبيعية'],
            ],
            [
                'title' => 'ريف دمشق: طبيعة وقرب من العاصمة',
                'body'  => 'ريف دمشق يحتضن قرى خلابة ومناظر جبلية قريبة من العاصمة، مناسبة لهروب يومي أو لنزهات نهاية الأسبوع.',
                'tags'  => ['ريف دمشق','طبيعية','ترفيهية'],
            ],
            [
                'title' => 'حمص: قلب نابض وتاريخ حي',
                'body'  => 'حمص تجمع بين الطابع الحضري والأسواق التقليدية، وتعتبر نقطة لقاء ثقافي في وسط البلاد.',
                'tags'  => ['حمص','ثقافية'],
            ],
            [
                'title' => 'السويداء: هدوء وجمال ريفي',
                'body'  => 'السويداء معالمها الريفية وفرص المشي في الطبيعة تجعلها وجهة مناسبة للباحث عن الهدوء والهواء النقي.',
                'tags'  => ['السويداء','طبيعية'],
            ],
            [
                'title' => 'دير الزور ونهر الفرات',
                'body'  => 'ضفاف الفرات حول دير الزور تروي قصصاً قديمة عن حضارات وطرق تجارة امتدت عبر التاريخ.',
                'tags'  => ['دير الزور','تاريخية','طبيعية'],
            ],
            [
                'title' => 'مقاصد ترفيهية صغيرة',
                'body'  => 'توجد في المدن والريف أماكن ترفيهية صغيرة مناسبة للعائلات: حدائق، مقاهي تقليدية، ومناطق نزهة قريبة.',
                'tags'  => ['ترفيهية'],
            ],
            [
                'title' => 'المأكولات السورية في سطور',
                'body'  => 'المطبخ السوري غني بالأطباق التقليدية مثل الكبة والمقلوبة واليبرك، وهو جزء من التراث الثقافي الذي يجمع الناس.',
                'tags'  => ['ثقافية','طبيعية'],
            ],
            [
                'title' => 'الطبيعة المتنوعة في سوريا',
                'body'  => 'من السواحل المتوسطية إلى البادية والجبال، سوريا تقدم تنوعاً طبيعياً يسمح بتجارب مختلفة للسياح المحليين والدوليين.',
                'tags'  => ['طبيعية'],
            ],
        ];

        DB::transaction(function () use ($articlesData) {
            foreach ($articlesData as $i => $data) {
                $article = Article::create([
                    'title' => $data['title'],
                    'body'  => $data['body'],
                ]);

                // create tiny placeholder PNG (1x1) and save
                $pngData = base64_decode(
                    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII='
                );
                $filename = 'articles/seed-' . $article->id . '-' . Str::random(8) . '.png';
                Storage::disk('public')->put($filename, $pngData);

                $article->media()->create([
                    'url' => $filename,
                ]);

                // attach tags (create TagName if needed, user_id left null on tag_names)
                $names = array_slice($data['tags'], 0, 5);
                $rows = [];
                foreach ($names as $name) {
                    $tagName = TagName::firstOrCreate(
                        ['body' => $name, 'follow_to' => 'article'],
                        []
                    );
                    $rows[] = [
                        'tag_name_id' => $tagName->id,
                        'user_id'     => null,
                        'article_id'  => $article->id,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
                if (!empty($rows)) {
                    DB::table('tags')->insert($rows);
                }
            }
        });
    }
}
