<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run()
    {
        // حول التطبيق
        Setting::create([
            'type' => 'about_app',
            'title' => 'عن تطبيق "لفة بسوريا"',
            'description' => 'تطبيق سياحي يهدف إلى تعريف المستخدمين بأجمل الوجهات السياحية في سوريا، مع توفير معلومات شاملة وخدمات مرافقة.',
            'category' => 'app'
        ]);

        Setting::create([
            'type' => 'about_app',
            'title' => 'رؤيتنا',
            'description' => 'نهدف إلى جعل السياحة في سوريا أكثر سهولة ومتعة من خلال التكنولوجيا والمحتوى الموثوق.',
            'category' => 'app'
        ]);

        // سياسة الخصوصية
        Setting::create([
            'type' => 'privacy_policy',
            'title' => 'المعلومات التي نجمعها',
            'description' => 'نجمع بيانات الاتصال الأساسية وبعض المعلومات التقنية لتحسين تجربتك.',
            'category' => 'app'
        ]);

        Setting::create([
            'type' => 'privacy_policy',
            'title' => 'كيف نستخدم المعلومات',
            'description' => 'نستخدم بياناتك لتقديم الدعم وتحسين خدمات التطبيق فقط.',
            'category' => 'app'
        ]);

        // الأسئلة الشائعة
        Setting::create([
            'type' => 'common_question',
            'title' => 'هل التطبيق يعمل بدون إنترنت؟',
            'description' => 'يمكنك تصفح بعض المعلومات المحملة مسبقاً، لكن يتطلب تحديث البيانات الاتصال بالإنترنت.',
            'category' => 'app'
        ]);

        Setting::create([
            'type' => 'common_question',
            'title' => 'هل يمكنني حجز جولات سياحية من خلال التطبيق؟',
            'description' => 'حالياً يمكنك استعراض الجولات السياحية والتواصل مع المنظمين مباشرة.',
            'category' => 'app'
        ]);
    }
}
