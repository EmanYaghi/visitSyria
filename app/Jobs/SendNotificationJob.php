<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $title;
    protected $message;

    public function __construct(User $user, $title, $message)
    {
        $this->user = $user;
        $this->title = $title;
        $this->message = $message;
    }

    public function handle(NotificationService $notificationService)
    {
        $notificationService->send($this->user, $this->title, $this->message);
    }
}
