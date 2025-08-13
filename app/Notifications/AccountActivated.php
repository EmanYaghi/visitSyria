<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Exception;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;

class AccountActivated extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {
        $message = new FcmMessage(
            notification: new FcmNotification(
                title: 'Account Activated',
                body: 'Your account has been activated.',
            )
        );
        $message->data([
            'type' => 'account_activated',
            'user_id' => $notifiable->id,
            'timestamp' => now()->toDateTimeString(),
            'action_url' => '/account'
        ]);

        Log::info('Sending FCM', [
            'token' => $notifiable->fcm_token,
            'payload' => $message->toArray()
        ]);

        return $message;
    }
}
