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
    public function register($request)
    {
        $user = User::create([
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
        ]);

        $user->verification_code = random_int(1000, 9999);
        $user->verification_code_sent_at = now();
        $user->save();

        Mail::to($user->email)->send(new VerifyEmail($user));

        return [
            'message' => 'User registered successfully! Please check your email for the verification code.',
            'code' => 201,
        ];
    }

    public function appendRolesAndPermissions($user)
    {
        $user['roles'] = $user->roles->pluck('name');
        $user['permissions'] = $user->permissions->pluck('name');
        return $user;
    }

    public function verifyEmail($request)
    {
        $token = null;
        $user = User::where('email', $request['email'])->first();

        if (!$user) {
            return ['message' => 'User not found', 'token' => $token, 'code' => 404];
        }

        if ($user->verification_code === $request['code']) {
            if (now()->diffInMinutes($user->verification_code_sent_at) > 10) {
                return ['message' => 'The verification code has expired.', 'token' => $token, 'code' => 400];
            }

            if ($user->verification_attempts >= 5) {
                return ['message' => 'You have exceeded the allowed number of attempts.', 'token' => $token, 'code' => 403];
            }

            $user->update([
                'email_verified_at' => now(),
                'is_verified' => true,
                'verification_code' => null,
                'verification_attempts' => 0,
            ]);

            $token = JWTAuth::fromUser($user);

            return ['message' => 'Email verified successfully!', 'token' => $token, 'code' => 200];
        }

        $user->increment('verification_attempts');
        return ['message' => 'Invalid verification code!', 'token' => $token, 'code' => 400];
    }

    public function verifyCode($request)
    {
        $user = User::where('email', $request['email'])->first();

        if (!$user) {
            return ['message' => 'User not found', 'code' => 404];
        }

        if ($user->verification_code === $request['code']) {
            if (now()->diffInMinutes($user->verification_code_sent_at) > 10) {
                return ['message' => 'The verification code has expired.', 'code' => 400];
            }

            if ($user->verification_attempts >= 5) {
                return ['message' => 'You have exceeded the allowed number of attempts.', 'code' => 403];
            }

            return ['message' => 'Code verified successfully!', 'code' => 200];
        }

        $user->increment('verification_attempts');
        return ['message' => 'Invalid verification code!', 'code' => 400];
    }

    public function resendVerificationCode($request)
    {
        $user = User::where('email', $request['email'])->first();

        if (!$user) {
            return ['message' => 'User not found!', 'code' => 404];
        }

        $user->update([
            'verification_code' => random_int(1000, 9999),
            'verification_code_sent_at' => now(),
        ]);

        Mail::to($user->email)->send(new VerifyEmail($user));

        return ['message' => 'Verification code resent successfully!', 'code' => 200];
    }

    public function forgetPassword($request)
    {
        $user = User::where('email', $request['email'])->first();

        if (!$user) {
            return ['message' => 'User not found!', 'code' => 404];
        }

        $user->update([
            'verification_code' => random_int(1000, 9999),
            'verification_code_sent_at' => now(),
        ]);

        Mail::to($user->email)->send(new PasswordReset($user));

        return ['message' => 'Password reset code sent successfully!', 'code' => 200];
    }

    public function resetPassword($request)
    {
        $user = User::where('email', $request['email'])->first();
        $token = null;

        if (!$user) {
            return ['message' => 'User not found!', 'token' => $token, 'code' => 404];
        }

        if ($user->verification_code === $request['code']) {
            if (now()->diffInMinutes($user->verification_code_sent_at) > 10) {
                return ['message' => 'The password reset code has expired.', 'token' => $token, 'code' => 400];
            }

            $user->update([
                'password' => Hash::make($request['new_password']),
                'verification_code' => null,
                'verification_code_sent_at' => null,
            ]);

            $token = JWTAuth::fromUser($user);

            return ['message' => 'Password has been reset successfully!', 'token' => $token, 'code' => 200];
        }

        return ['message' => 'Invalid password reset code.', 'token' => $token, 'code' => 400];
    }

    public function changePassword($request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return ['message' => 'User not found!', 'token' => null, 'code' => 404];
        }

        if (!Hash::check($request['old_password'], $user->password)) {
            return ['message' => 'The password is wrong', 'token' => null, 'code' => 401];
        }

        $user->update(['password' => Hash::make($request['new_password'])]);
        $token = JWTAuth::fromUser($user);

        return ['message' => 'Password has been reset successfully!', 'token' => $token, 'code' => 200];
    }

    public function login($request)
    {
        $user = User::where('email', $request['email'])->first();
        $token = null;

        if (!$user) {
            return ['message' => 'User not found', 'token' => $token, 'code' => 404];
        }

        if (!JWTAuth::attempt($request)) {
            return ['message' => 'User email & password do not match our records.', 'token' => $token, 'code' => 401];
        }

        if (!$user->is_verified) {
            return ['message' => 'Email not verified. Please verify your email first.', 'token' => $token, 'code' => 403];
        }

        $token = JWTAuth::fromUser($user);

        return ['message' => 'User logged in successfully', 'token' => $token, 'user' => $user, 'code' => 200];
    }

    public function logout()
    {
        $user = Auth::user();

        if (!$user) {
            return ['message' => 'Invalid token', 'code' => 404];
        }

        Auth::logout();
        return ['message' => 'User logged out successfully', 'code' => 200];
    }

    public function deleteAccount($request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return ['message' => 'User not found', 'code' => 404];
        }

        if ($user->email === $request['email'] && Hash::check($request['password'], $user->password)) {
            $user->delete();
            return ['message' => 'User account deleted successfully', 'code' => 200];
        }

        return ['message' => 'Email or password is wrong', 'code' => 400];
    }

     public function updatePreference( $request)
    {
        $user = Auth::user();
        $preference=Preference::where('user_id',$user->id);
        $preference->update($request);
        $message= 'preferences updated';
        $code=200;
        return ['message'=>$message,'code'=>$code];
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
            'profile'=>$user->profile
        ];
    }
    public function updateProfile( $request)
    {
        $user = Auth::user();
        $user->profile->update($request);
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
        return ['message'=>$message,'code'=>$code];
    }
      public function updateAdminProfile( $request)
    {
        $user = Auth::user();
        $user->adminProfile->update($request);
        $message= 'profile updated';
        $code=200;
        return ['message'=>$message,'code'=>$code];
    }
}
