<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use Illuminate\Console\Command;
use App\Models\Event;
use App\Models\Trip;
use Carbon\Carbon;

class NotifyUsersBeforeStartBooking extends Command
{
    protected $signature = 'notify:before-trip';
    protected $description = 'إرسال إشعار للمستخدمين قبل يوم من موعد الرحلة أو الحدث';

    public function handle()
    {
        $tomorrow = Carbon::tomorrow()->toDateString();
        $trips = Trip::whereDate('start_date', $tomorrow)->with(['bookings.user'])->get();
        foreach ($trips as $trip) {
            foreach ($trip->bookings->where('is_paid', true) as $booking) {
                if ($booking->user) {
                    SendNotificationJob::dispatch(
                        $booking->user,
                        'تذكير بالرحلة',
                        "رحلتك إلى {$trip->name} غداً ({$trip->start_date->format('Y-m-d H:i')})"
                    );
                }
            }
        }
        $events = Event::whereDate('start_date', $tomorrow)->with(['bookings.user'])->get();
        foreach ($events as $event) {
            foreach ($event->bookings->where('is_paid', true) as $booking) {
                if ($booking->user) {
                    SendNotificationJob::dispatch(
                        $booking->user,
                        'تذكير بالفعالية',
                        "الفعالية {$event->name} غداً ({$event->start_date->format('Y-m-d H:i')})"
                    );
                }
            }
        }

        $this->info(' تم جدولة التذكيرات بنجاح');
    }
}
