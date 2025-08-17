<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Support;
use App\Models\User;
use Carbon\Carbon;

class SupportSeeder extends Seeder
{
    public function run(): void
    {
        $emails = [
            'client@example.com',
            'client2@example.com',
            'tourguide@example.com',
            'hotel@example.com',
            'restaurant@example.com',
            'localguide@example.com',
            'visitor1@example.com',
            'visitor2@example.com',
            'visitor3@example.com',
            'admin@example.com',
        ];

        $users = [];
        foreach ($emails as $email) {
            $users[] = User::firstOrCreate(
                ['email' => $email],
                ['password' => bcrypt('password'), 'stripe_customer_id' => null]
            );
        }

        $positive = [
            'خدمة ممتازة وتجربة لا تُنسى',
            'المرشد كان على دراية ومهني للغاية',
            'المكان منظّم ونظيف والتعامل كان رائعاً',
            'الطعام المحلي كان مميّز وأسعاره معقولة',
            'إقامة مريحة وموقع متميز بالقرب من المعالم',
            'الرحلة كانت متقنة والتنظيم محترف',
            'التسهيلات كانت ممتازة وسهّلت الزيارة',
            'طاقم العمل ودود وسريع في الاستجابة',
            'منظر طبيعي خلاب وأنشطة ممتعة',
            'خدمة النقل كانت مريحة وفي الوقت المحدد'
        ];

        $negative = [
            'التنظيم كان ضعيفاً والوقت غير محسوب جيداً',
            'واجهنا تأخيراً كبيراً في الاستقبال والخدمة',
            'المكان بحاجة لصيانة وتحسينات ملحوظة',
            'الجولات لم تكن كما وُعدنا بها',
            'المعلومات المقدمة عن المعالم كانت ناقصة',
            'الطعام لم يكن بالمستوى المتوقع',
            'الأسعار مرتفعة مقابل جودة الخدمة',
            'الموظفون غير متعاونين بما يكفي',
            'ظروف الإقامة لم تكن نظيفة كما يجب',
            'خدمة العملاء بطيئة وغير فعالة'
        ];

        $neutral = [
            'زيارة جيدة لكن هناك مناطق تحتاج تحسين',
            'تجربة متوسطة ربما أعود لاحقاً',
            'مكان جيد للمشاهدة لكن غير مناسب للعائلات',
            'خدمة مقبولة ولكن التوقعات كانت أعلى',
            'النشاط مناسب لمن يحب المغامرات الخفيفة',
            'الموقع جميل لكن الخدمات حوله قليلة'
        ];

        $monthlyCounts = [5, 8, 12, 20, 15, 10, 7, 9, 14, 11, 6, 18];

        foreach ($monthlyCounts as $monthIndex => $count) {
            $month = $monthIndex + 1;
            for ($i = 0; $i < $count; $i++) {
                $user = $users[array_rand($users)];
                $r = rand(1, 100);
                if ($r <= 60) {
                    $rating = rand(4, 5);
                    $comment = $positive[array_rand($positive)];
                } elseif ($r <= 85) {
                    $rating = rand(2, 3);
                    $comment = $neutral[array_rand($neutral)];
                } else {
                    $rating = rand(1, 2);
                    $comment = $negative[array_rand($negative)];
                }

                $day = rand(1, 28);
                $hour = rand(8, 20);
                $minute = rand(0, 59);
                $created = Carbon::create(2025, $month, $day, $hour, $minute, rand(0, 59));

                Support::create([
                    'user_id' => $user->id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'category' => (rand(1, 100) <= 12) ? 'admin' : 'app',
                    'created_at' => $created,
                    'updated_at' => $created,
                ]);
            }
        }
    }
}
