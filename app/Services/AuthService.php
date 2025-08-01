<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\PasswordReset;
use App\Mail\VerifyEmail;
use App\Models\AdminProfile;
use App\Models\Preference;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthService
{
     public function register( $request)
    {
        $user=User::create([
            'email'=>$request['email'],
            'password'=>Hash::make($request['password']),
        ]);
        $user->verification_code=random_int(1000, 9999);
        $user->verification_code_sent_at=now();
        $user->save();
        Mail::to($user->email)->send(new VerifyEmail($user));
        $code=201;
        $message='User registered successfully! Please check your email for the verification code.';
        return ['message'=>$message,'code'=>$code];
    }

    public function appendRolesAndPermissions($user)
    {
        $user['roles'] = $user->roles->pluck('name');
        $user['permissions'] = $user->permissions->pluck('name');
        return $user;
    }

    public function verifyEmail($request)
    {
        $token =null;
        $user = User::where('email', $request['email'])->first();
        if (!$user) {
            $message='user not found';
            $code= 404;
        }
        else if ($user->verification_code == $request['code'] ) {
            if (now()->diffInMinutes($user->password_reset_code_sent_at) > 10) {
                $message='The password reset code has expired.';
                $code=400;
            }
            else if ($user->verification_attempts >= 5) {
                $message="You have exceeded the allowed number of attempts.";
                $code=403;
            }
            else{
                $user->email_verified_at = now();
                $user->is_verified=true;
                $user->verification_code = null;
                $user->verification_attempts = 0;
                $token=JWTAuth::fromUser($user);
                $user->save();
                $message= 'Email verified successfully!';
                $code=200;
            }
        }else{
            $user->verification_attempts++;
            $user->save();
            $message= 'Invalid verification code!';
            $code=400;
        }
        return ['message'=>$message,'token'=>$token,'code'=>$code];
    }

    public function verifyCode($request)
    {
        $token =null;
        $user = User::where('email', $request['email'])->first();
        if (!$user) {
            $message='user not found';
            $code= 404;
        }
        else if ($user->verification_code == $request['code'] ) {
            if (now()->diffInMinutes($user->password_reset_code_sent_at) > 10) {
                $message='The password reset code has expired.';
                $code=400;
            }
            else if ($user->verification_attempts >= 5) {
                $message="You have exceeded the allowed number of attempts.";
                $code=403;
            }
            else{
                $message= 'Code verified successfully!';
                $code=200;
            }
        }else{
            $user->verification_attempts++;
            $user->save();
            $message= 'Invalid verification code!';
            $code=400;
        }
        return ['message'=>$message,'token'=>$token,'code'=>$code];
    }

    public function resendVerificationCode( $request)
    {
        $user = User::where('email', $request['email'])->first();
        if ($user) {
            $user->verification_code = random_int(1000, 9999);
            $user->verification_code_sent_at = now();
            $user->save();
            Mail::to($user->email)->send(new VerifyEmail($user));
            $message= 'Verification code resent successfully!';
            $code=200;
        }else{
            $message='User not found!';
            $code= 404;
        }
         return ['message'=>$message,'code'=>$code];
    }

     public function forgetPassword( $request)
    {
        $user = User::where('email', $request['email'])->first();
        if ($user) {
            $user->verification_code = random_int(1000, 9999);
            $user->verification_code_sent_at = now();
            $user->save();
            Mail::to($user->email)->send(new PasswordReset($user));
            $message= 'Password reset code sent successfully!';
            $code=200;
        }else{
            $message= 'User not found!';
            $code= 404;
        }
        return ['message'=>$message,'code'=>$code];
    }


    public function resetPassword($request)
    {
        $token=null;
        $user = User::where('email', $request['email'])->first();
        if ($user) {
            if ($user->verification_code == $request['code']) {
                if ($user->verification_code_sent_at&&now()->diffInMinutes($user->verification_code_sent_at) > 10) {
                    $message='The password reset code has expired.';
                    $code=400;
                }else{
                    $user->password = Hash::make($request['new_password']);
                    $token=JWTAuth::fromUser($user);
                    $user->verification_code = null;
                    $user->verification_code_sent_at = null;
                    $user->save();
                    $message= 'Password has been reset successfully!';
                    $code=200;
                }
            }
            else{
                $message='Invalid password reset code.';
                $code=400;
            }
        }else{
            $message='User not found!';
            $code=404;
        }
        return['token'=>$token,'message'=>$message,'code'=>$code];
    }

    public function changePassword($request)
    {
        $token=null;
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            if(Hash::check($request['old_password'],$user->password)){
                $user->password = Hash::make($request['new_password']);
                $user->save();
                $token=JWTAuth::fromUser($user);
                $message='Password has been reset successfully!';
                $code=200;
            }else{
                $message='The password is wrong';
                $code= 401;
            }
        }else{
            $message='User not found!';
            $code= 404;
        }
        return['token'=>$token,'message'=>$message,'code'=>$code];
    }
    public function login($request)
    {
        $user=User::where('email',$request['email'])->first();
        if($user )
        {
            if (! $token = JWTAuth::attempt($request)) {
                $message='user email & password does not match with our record.';
                $code=401;
            }else if(!$user->is_verified){
                $message='email not verified.please verify your email first';
                $code=403;
            }else{
                $token=JWTAuth::fromUser($user);
                $message='user logged in successfully';
                $code=200;
            }
        }else{
            $message='user not found';
            $code=404;
        }
        return['user'=>$user,'message'=>$message,'token'=>$token,'code'=>$code];
    }

    public function logout()
    {
        $user=Auth::user();
        if($user)
        {
            Auth::logout();
            $message='user logged out successfuly';
            $code=200;
        }else{
            $message='invalid token';
            $code=404;
        }
        return['message'=>$message,'code'=>$code];
    }
     public function deleteAccount($request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if($user)
        {
            if($user->email==$request['email']&&Hash::check($request['password'],$user->password)){
                $user->delete();
                $message='user account deleted successfuly';
                $code=200;
            }else{
                $message='email or password is wrong ';
                $code=400;
            }
        }else{
            $message='user not found';
            $code=404;
        }
        return['message'=>$message,'code'=>$code];
    }
     public function setPreference( $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        Preference::create([
            'user_id'=>$user->id,
            ...$request
        ]);
        $message= 'preferences created';
        $code=201;
        return ['message'=>$message,'code'=>$code];
    }
    public function setProfile( $request)
    {
        $user = Auth::user();
        Profile::create([
            'user_id'=>$user->id,
            ...$request
        ]);
        $message= 'profile created';
        $code=201;
        return [
            'message'=>$message,
            'code'=>$code,
            'profile'=>$user->load('profile')
        ];
    }
    public function updateProfile( $request)
    {
        $user = Auth::user();
        $user->profile->update($request);
        if($user->preference)
            $user->preference->update($request);
        else
            Preference::create(['user_id'=>$user->id,...$request]);
        $message= 'profile updated';
        $code=200;
        return ['message'=>$message,'code'=>$code,'profile'=>$user->profile];
    }
     public function setAdminProfile( $request)
    {
        $user = Auth::user();
        AdminProfile::create([
            'user_id'=>$user->id,
            ...$request
        ]);
        $message= 'profile created';
        $code=201;
        return ['adminProfile'=>$user->load('adminProfile'),'message'=>$message,'code'=>$code];
    }
      public function updateAdminProfile( $request)
    {
        $user = Auth::user();
        $user->adminProfile->update($request);
        $message= 'profile updated';
        $code=200;
        return ['adminProfile'=>$user->load('adminProfile'),'message'=>$message,'code'=>$code];
    }
}
