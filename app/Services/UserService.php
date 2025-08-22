<?php

namespace App\Services;

use App\Http\Resources\Auth\ProfileResource;
use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\ReservationResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserService
{
    public function allUser()
    {
        $users = User::role('client')->get();
        $user = Auth::user();
        if ($user->hasRole('super_admin')) {
            $users = UserResource::collection($users);
        }
        else{
            $users=null;
        }
        return [
            'users' => $users,
            'message' => 'this is all users',
            'code' => 200
        ];
    }
    public function getUserById($id)
    {
        $user = User::with('profile')
            ->withCount('posts')
            ->withCount(['bookings as reserved_trips_count' => function ($query) {
                $query->whereNotNull('trip_id');
            }])
            ->withCount(['bookings as reserved_events_count' => function ($query) {
                $query->whereNotNull('event_id');
            }])
            ->find($id);

        if (! $user) {
            return [
                'message' => 'not found',
                'code'    => 404,
            ];
        }

        $authUser = Auth::user();
        if (! $authUser) {
            return [
                'message' => 'unauthenticated',
                'code'    => 401,
            ];
        }

        if (! $authUser->hasRole('super_admin') && $authUser->id !== $user->id) {
            return [
                'message' => 'unauthorized',
                'code'    => 403,
            ];
        }

        return [
            'user'    => new UserResource($user),
            'message' => 'user retrieved',
            'code'    => 200,
        ];
    }
    public function mostActiveUsers()
    {
        $by=request()->query('by');
        $user = Auth::user();

        if($by=='post')
        {
            $users = User::role('client')->withCount('posts')
                ->orderBy('posts_count', 'desc')
                ->take(10)
                ->get();
        }
        else if($by=='event')
        {
            $users = User::role('client')->withCount(['bookings as event_bookings_count' => function ($query) {
                $query->whereNotNull('event_id');
            }])
            ->orderByDesc('event_bookings_count')
            ->take(10)
            ->get();

        }
        else if($by=='trip')
        {
            $users = User::role('client')->withCount(['bookings as trip_bookings_count' => function ($query) {
                $query->whereNotNull('trip_id');
            }])
            ->orderByDesc('trip_bookings_count')
            ->take(10)
            ->get();
        }
        else
        {
            return [
                'message' => 'by must be either trip or event or post',
                'code' => 400
            ];
        }
        if ($user->hasRole('super_admin')) {
            $users = UserResource::collection($users);
        }
        else{
            $users=null;
        }
        return [
            'users' => $users,
            'message' => 'this is all users',
            'code' => 200
        ];
    }
public function changeUserStatus($request)
{
    if (!Auth::user()->hasRole('super_admin')) {
        return [
            'message' => 'unauthorized',
            'code' => 403
        ];
    }

    $user = User::findOrFail($request['user_id']);
    $profile = $user->profile;
    if (! $profile) {
        // إذا لم يوجد بروفايل، أنشئ واحد فارغ لتجنب أخطاء الحفظ
        $profile = $user->profile()->create([]);
    }

    if (($request['status'] ?? '') === 'block') {
        $duration = $request['duration'] ?? null;

        if ($duration && in_array($duration, ['minute', 'hour', 'day', 'week', 'month', 'year'], true)) {
            switch ($duration) {
                case 'minute':
                    $date = now()->addMinute();
                    break;
                case 'hour':
                    $date = now()->addHour();
                    break;
                case 'day':
                    $date = now()->addDay();
                    break;
                case 'week':
                    $date = now()->addWeek();
                    break;
                case 'month':
                    $date = now()->addMonth();
                    break;
                case 'year':
                    $date = now()->addYear();
                    break;
                default:
                    $date = null;
                    break;
            }

            $profile->account_status = 'حظر مؤقت';
            $profile->date_of_unblock = $date;
        } else {
            $profile->account_status = 'حظر نهائي';
            $profile->date_of_unblock = null;
        }

    } elseif (($request['status'] ?? '') === 'unblock') {
        $profile->account_status = 'نشط';
        $profile->date_of_unblock = null;
    }

    $profile->save();

    return [
        'user' => new UserResource($user),
        'message' => 'user status changed',
        'code' => 200
    ];
}


    public function userActivities($id)
    {
        $user = User::find($id);
        if (!$user) {
            return [
                'message' => "not found",
                'code'    => 404
            ];
        }
        $type=request()->query('type');
        if($type=='post')
        {
            $activities=$user->posts;
            $activities= PostResource::collection($activities);
        }
        else if($type=='trip'||$type=='event')
        {
            $activities = $user->bookings()->whereNotNull($type.'_id')->where('is_paid',true)->get();
            $activities= ReservationResource::collection($activities);
        }
        else
            return [
                'message' => 'the type must be either trip or post or event',
                'code'    => 200,
            ];
         return [
            'activities'   => $activities,
            'message' => 'All '.$type.' retrieved.',
            'code'    => 200,
        ];

    }

}
