<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trip;
use Carbon\Carbon;

class UpdateTripsStatus extends Command
{
    protected $signature = 'trips:update-status';
    protected $description = 'تحديث حالة الرحلات حسب وقت البداية والمدة';

    public function handle()
    {
        $now = Carbon::now();
        Trip::where('status', 'لم تبدأ بعد')
            ->whereDate('start_date', '<=', $now)
            ->update(['status' => 'جارية حاليا']);
        $trips = Trip::where('status', 'جارية حاليا')->get();
        foreach ($trips as $trip) {
            $endDate = Carbon::parse($trip->start_date)->addDays($trip->days);
            if ($endDate->lte($now)) {
                $trip->update(['status' => 'منتهية']);
            }
        }
        $this->info('تم تحديث حالات الرحلات');
    }
}
