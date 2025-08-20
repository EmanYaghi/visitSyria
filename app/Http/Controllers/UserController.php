<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\BlockUserRequest;
use App\Http\Requests\ChangeUserRequest;
use App\Services\UserService;
use Illuminate\Http\Request;
use Throwable;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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

}
