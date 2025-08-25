<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserNotification extends Notification
{
    use Queueable;
    private $message,$title;
    public function __construct($title,$message)
    {
        $this->message = $message;
        $this->title = $title;
    }
    public function via($notifiable)
    {
        return ['database'];
    }
    public function toDatabase($notifiable)
    {
        return [
            'title'=>$this->title,
            'message' => $this->message,
            'time' => now()->toDateTimeString(),
        ];
    }
}
