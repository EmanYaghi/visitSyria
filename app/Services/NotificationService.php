<?php

namespace App\Services;

use App\Models\Notification as NotificationModel;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class NotificationService
{

    public function index()
    {
        return auth()->user()->notifications;
    }


    public function send($user, $title, $message, $type = 'basic')
{
    // Path to the service account key JSON file
    $serviceAccountPath = storage_path('app/visitsyria-817d3-firebase-adminsdk-fbsvc-3a4ba10c8a.json');

    // Initialize the Firebase Factory with the service account
    $factory = (new \Kreait\Firebase\Factory)->withServiceAccount($serviceAccountPath);

    // Create the Messaging instance
    $messaging = $factory->createMessaging();

    // Prepare the notification array
    $notification = [
        'title' => $title,
        'body'  => $message,
        'sound' => 'default',
    ];

    // Additional data payload
    $data = [
        'type'    => $type,
        'id'      => $user->id,
        'message' => $message,
    ];

    // Get all FCM tokens for the user
    $fcmTokens = $user->fcmTokens()->pluck('token')->toArray();

    if (empty($fcmTokens)) {
        // لا يوجد Tokens لإرسال الإشعار
        return 0;
    }

    try {
        foreach ($fcmTokens as $fcmToken) {
            $cloudMessage = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification)
                ->withData($data);

            // Send the notification
            $messaging->send($cloudMessage);
        }

        // Save the notification to the database
        \App\Models\Notification::create([
            'type' => 'App\Notifications\UserFollow',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id'   => $user->id,
            'data' => json_encode([
                'user'    => $user->first_name . ' ' . $user->last_name,
                'message' => $message,
                'title'   => $title,
            ]),
        ]);

        return 1;
    } catch (\Kreait\Firebase\Exception\MessagingException $e) {
        Log::error($e->getMessage());
        return 0;
    } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
        Log::error($e->getMessage());
        return 0;
    }
}

    public function markAsRead($notificationId): bool
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);

        if(isset($notification)) {
            $notification->markAsRead();
            return true;
        }else return false;
    }

    public function destroy($id): bool
    {
        $notification = auth()->user()->notifications()->findOrFail($id);

        if(isset($notification)) {
            $notification->delete();
            return true;
        }else return false;
    }

}
