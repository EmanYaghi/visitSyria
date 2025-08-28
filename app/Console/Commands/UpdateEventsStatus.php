<?php

namespace App\Console\Commands;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateEventsStatus extends Command
{
 protected $signature = 'events:update-status';
    protected $description = 'تحديث حالة الاحداث حسب وقت البداية والمدة';

    public function handle()
    {
        $now = Carbon::now();
        Event::where('status2', 'لم تبدأ بعد')
            ->whereDate('date', '<=', $now)
            ->update(['status2' => 'جارية حاليا']);
        $events = Event::where('status2', 'جارية حاليا')->get();
        foreach ($events as $event) {
            $endDate = Carbon::parse($event->date)->addDays($event->duration_days);
            if ($endDate->lte($now)) {
                $event->update(['status2' => 'منتهية']);
            }
        }
        $this->info('تم تحديث حالات الاحداث');
    }
}
