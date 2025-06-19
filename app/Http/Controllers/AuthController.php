<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\DeleteAccountRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LoginWithGoogleRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendCodeRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Services\AuthService;
use App\Services\GoogleAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Throwable;
class AuthController extends Controller
{
    protected AuthService $authService;
    protected GoogleAuthService $googleAuthService;
    public function __construct(AuthService $authService, GoogleAuthService $googleAuthService)
    {
        $this->authService = $authService;
        $this->googleAuthService = $googleAuthService;
    }

    public function register(RegisterRequest $request)
    {
        $data=[];
        try{
            $data=$this->authService->register($request->validated());
            return response()->json(["message"=>$data['message']],$data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
             return response()->json(["message"=>$message]);
        }
    }
    public function verifyEmail(VerifyCodeRequest $request)
    {
        $data=[];
        try{
            $data=$this->authService->verifyEmail($request->validated());
            return response()->json(["message"=>$data['message'],"token"=>$data['token']],$data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
     public function verifyCode(VerifyCodeRequest $request)
    {
        $data=[];
        try{
            $data=$this->authService->verifyCode($request->validated());
            return response()->json(["message"=>$data['message'],"token"=>$data['token']],$data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function resendVerificationCode(SendCodeRequest $request)
    {
        $data=[];
        try{
            $data=$this->authService->resendVerificationCode($request->validated());
            return response()->json(["message"=>$data['message']],$data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function forgetPassword(SendCodeRequest $request)
    {
        $data=[];
        try{
            $data=$this->authService->forgetPassword($request->validated());
            return response()->json(["message"=>$data['message']],$data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function resetPassword(ResetPasswordRequest $request)
    {
        $data=[];
        try{
            $data=$this->authService->resetPassword($request->validated());
            return response()->json(["message"=>$data['message'],"token"=>$data['token']],$data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function changePassword(ChangePasswordRequest $request)
    {
        $data=[];
        try{
            $data=$this->authService->changePassword($request->validated());
            return response()->json(["message"=>$data['message'],"token"=>$data['token']],$data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function login(LoginRequest $request)
    {
        $data=[];
        try{
            $data=$this->authService->login($request->validated());
            return response()->json(["meassage"=>$data['message'],"token"=>$data['token']],$data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["meassage"=>$message]);
        }
    }
        public function loginWithGoogle(LoginWithGoogleRequest $request)
    {
        try {
            $data = $this->googleAuthService->authenticate($request->validated());
            return response()->json([
                'message'      => $data['message'],
                'access_token' => $data['token'],
                'user'         => $data['user'],
            ], $data['code']);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function profile()
    {
        $data=[];
        try{
            $data=$this->authService->profile();
            return response()->json(["message"=>$data['message'],"profile"=>$data['user']]);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function logout()
    {
        $data=[];
        try{
            $data=$this->authService->logout();
            return response()->json(["message"=>$data['message']],$data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
            return response()->json(["message"=>$message]);
        }
    }
    public function deleteAccount(DeleteAccountRequest $request)
    {
        $data=[];
        try{
            $data=$this->authService->deleteAccount($request->validated());
            return response()->json(["message"=>$data['message']],$data['code']);
        }catch(Throwable $th){
            $message=$th->getMessage();
             return response()->json(["message"=>$message]);
        }
    }

}
