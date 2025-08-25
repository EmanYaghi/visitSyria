<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Carbon\Carbon;

class DeleteUnpaidBookings extends Command
{
    protected $signature = 'bookings:delete-unpaid';
    protected $description = 'حذف الحجوزات التي مرّ عليها أكثر من نصف ساعة ولم تدفع';

    public function handle()
    {
        $deleted = Booking::where('is_paid', false)
            ->where('created_at', '<', Carbon::now()->subMinutes(30))
            ->delete();

        $this->info("تم حذف {$deleted} من الحجوزات غير المدفوعة.");
    }
}
