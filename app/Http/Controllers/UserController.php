<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\BlockUserRequest;
use App\Http\Requests\ChangeUserRequest;
use App\Http\Requests\NotificationRequest;
use App\Services\NotificationService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Throwable;

class UserController extends Controller
{
    protected $userService;
    protected $notificationService;
    public function __construct(UserService $userService,NotificationService $notificationService)
    {
        $this->userService = $userService;
        $this->notificationService=$notificationService;
    }

    public function allUser()
    {
        $data=[];
        try{
            $data=$this->userService->allUser();
            return response()->json(["users"=>$data['users']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function getUser($id)
    {
        try {
            $data = $this->userService->getUserById($id);
            return response()->json([
                'user'    => $data['user'] ?? null,
                'message' => $data['message'] ?? null
            ], $data['code'] ?? 200);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function mostActiveUsers()
    {
        $data=[];
        try{
            $data=$this->userService->mostActiveUsers();
            return response()->json(["users"=>$data['users']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }

    }

    public function changeUserStatus(ChangeUserRequest $request)
    {
        $data=[];
        try{
            $data=$this->userService->changeUserStatus($request->validated());
            return response()->json(["user"=>$data['user']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }

    public function userActivities($id)
    {
        $data=[];
        try{
            $data=$this->userService->userActivities($id);
            return response()->json(["activities"=>$data['activities']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }

    public function destroyNotification($id)
    {
        $data=[];
        try{
            $data=$this->notificationService->destroy($id);
            return response()->json(["message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function getAllNotifications($type)
    {
        $data=[];
        try{
            $data=$this->notificationService->getAllNotifications($type);
            return response()->json(["notifications"=>$data['notifications']??null,"message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function sendNotificationBySA(NotificationRequest $request)
    {
        $data=[];
        try{
            $data=$this->notificationService->sendNotificationBySA($request->validated());
            return response()->json(["message" =>$data['message']], $data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
}
