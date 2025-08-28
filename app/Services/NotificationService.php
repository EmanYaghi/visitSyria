<?php

namespace App\Services;

use App\Http\Resources\NotificationResource;
use App\Jobs\SendNotificationJob;
use App\Models\Notification as NotificationModel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\Str;

class NotificationService
{

    public function index()
    {
        return Auth::user()->notifications;
    }
    public function send($user, $title, $message, $type = 'basic')
    {
        $serviceAccountPath = storage_path('app/firebase/visitsyria-1886594ce621.json');
        $factory = (new \Kreait\Firebase\Factory)->withServiceAccount($serviceAccountPath);
        $messaging = $factory->createMessaging();
        $notification = [
            'title' => $title,
            'body'  => $message,
            'sound' => 'default',
        ];
        $data = [
            'type'    => $type,
            'id'      => $user->id,
            'message' => $message,
        ];
        $fcmTokens = $user->fcmTokens()->pluck('token')->toArray();
        if (empty($fcmTokens)) {
            return 0;
        }
        try {
            foreach ($fcmTokens as $fcmToken) {
                $cloudMessage = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $fcmToken)
                    ->withNotification($notification)
                    ->withData($data);
                $messaging->send($cloudMessage);
            }

            \App\Models\Notification::create([
                'id'              => Str::uuid(),
                'type'            => 'App\Notifications\UserNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id'   => $user->id,
                'data'            => json_encode([
                    'title'   => $title,
                    'message' => $message,
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
    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if(isset($notification)) {
            $notification->delete();
             return [
                "message" => 'the notification deleted',
                'code' => 200
            ];
        }else
            return [
                "message" => 'the notification not found',
                'code' => 404
            ];
    }
    public function getAllNotifications($type)
    {
        if($type=='read')
            $notifications = Auth::user()->readNotifications;
        else
        {
            $notifications = Auth::user()->unreadNotifications;
            foreach($notifications as $n)
                $n->markAsRead();
        }
        return [
            "notifications"=>NotificationResource::collection($notifications),
            "message" => 'these are notifiction that '.$type,
            'code' => 200
        ];
    }
     public function sendNotificationBySA($request)
    {
        if (!Auth::user()->hasRole('super_admin')) {
            return [
                'message' => 'unauthorized',
                'code' => 403
            ];
        }
        if($request['category']=='company')
        {
            foreach(User::role('admin')->get()  as $user){
                \App\Models\Notification::create([
                    'id'              => Str::uuid(),
                    'type'            => 'App\Notifications\UserNotification',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id'   => $user->id,
                    'data'            => json_encode([
                        'title'   => $request['title'],
                        'message' => $request['description'],
                    ]),
                ]);
            }
        }
        else
        {
            foreach(User::role('client')->get()  as $user){
                SendNotificationJob::dispatch($user, $request['title'], $request['description']);
            }
        }
        return [
            "message" => 'the notification send for all '.$request['category'],
            'code' => 200
        ];
    }
}
