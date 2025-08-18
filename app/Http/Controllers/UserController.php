<?php

namespace App\Http\Controllers;

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
}
