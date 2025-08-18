<?php

namespace App\Services;

use App\Http\Resources\Auth\ProfileResource;
use App\Http\Resources\Auth\UserResource;
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
}
