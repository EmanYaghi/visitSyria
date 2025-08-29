<?php

namespace App\Services;

use App\Http\Resources\Auth\AdminProfileResource;
use App\Http\Resources\Auth\AdminResource;
use App\Http\Resources\Auth\ProfileResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\companyWithEarningResource;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\PasswordReset;
use App\Mail\VerifyEmail;
use App\Models\AdminProfile;
use App\Models\FcmToken;
use App\Models\Preference;
use App\Models\Profile;
use App\Notifications\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
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
                if (!empty($request['fcm_token'])) {
                    FcmToken::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'token' => $request['fcm_token']
                        ],
                        [
                            'user_id' => $user->id
                        ]
                    );
                }
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
                if (!empty($request['fcm_token'])) {
                    FcmToken::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'token' => $request['fcm_token']
                        ],
                        [
                            'user_id' => $user->id
                        ]
                    );
                }
                $token=JWTAuth::fromUser($user);
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
                    if (!empty($request['fcm_token'])) {
                        FcmToken::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'token' => $request['fcm_token']
                            ],
                            [
                                'user_id' => $user->id
                            ]
                        );
                    }
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
            if (! $token = JWTAuth::attempt(['email'=>$request['email'],'password'=>$request['password']])) {
                $message='user email & password does not match with our record.';
                $code=401;
            }else if(!$user->is_verified){
                $message='email not verified.please verify your email first';
                $code=403;
            }else{
                if (!empty($request['fcm_token'])) {
                    if ($user->hasRole('client')&&$user->fcmTokens()->count() >= 1&&!$user->fcmTokens()->where('token',$request['fcm_token'])->exists())
                    {
                        $this->notificationService->send(
                            $user,
                            'تنبيه أمني',
                            'تم الدخول إلى حسابك من جهاز آخر',
                            'تحذير'
                        );
                    }
                    FcmToken::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'token' => $request['fcm_token']
                        ],
                        [
                            'user_id' => $user->id
                        ]
                    );
                }
                $message='user logged in successfully';
                $code=200;
            }
        }else{
            $message='user not found';
            $code=404;
        }
        return['user'=>$user,'message'=>$message,'token'=>$token,'code'=>$code];
    }

    public function logout(Request $request)
    {
        $request->validate(['fcm_token'=>'required']);
        $user=Auth::user();
        if($user)
        {
            if ($request->has('fcm_token')) {
                FcmToken::where('token', $request->fcm_token)->delete();
            }
            JWTAuth::invalidate(JWTAuth::getToken());
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
        $user->assignRole('client');
        $message= 'profile created';
        $code=201;
        return [
            'message'=>$message,
            'code'=>$code,
            'profile'=>new ProfileResource($user->load('profile'))
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
        return ['message'=>$message,'code'=>$code,'profile'=>new ProfileResource($user->profile)];
    }
     public function setAdminProfile( $request)
    {
        $user = Auth::user();
        $p=AdminProfile::create([
            'user_id'=>$user->id,
            ...$request
        ]);
        if (isset($request['documents']) && is_array($request['documents'])) {
            foreach ($request['documents'] as $document) {
                if ($document instanceof \Illuminate\Http\UploadedFile) {
                    $url = $document->store('document_images');
                    $user->media()->create(['url' => $url]);
                }
            }
        }
        $user->assignRole('admin');
        \App\Models\Notification::create([
                'id'              => Str::uuid(),
                'type'            => 'App\Notifications\UserNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id'   => User::role('super_admin')->first()->id,
                'data'            => json_encode([
                    'title'   => 'شركة جديدة',
                    'message' => 'هناك طلب من شركة لتوافق عليه او ترفضه',
                ]),
            ]);
        $message= 'profile created';
        $code=201;
        return ['adminProfile'=>new companyWithEarningResource($p),'message'=>$message,'code'=>$code];
    }
      public function updateAdminProfile( $request)
    {
        $user = Auth::user();
        $user->adminProfile->update($request);
        if (isset($request['documents']) && is_array($request['documents'])) {
            foreach ($request['documents'] as $document) {
                if ($document instanceof \Illuminate\Http\UploadedFile) {
                    $url = $document->store('document_images');
                    $user->media()->create(['url' => $url]);
                }
            }
        }
        $user->assignRole('admin');
        $message= 'profile updated';
        $code=200;
        return ['adminProfile'=>new companyWithEarningResource($user->adminProfile),'message'=>$message,'code'=>$code];
    }

    public function registerCompanyBySuperAdmin( $request)
    {
        $superAdmin = Auth::user();
        if(!$superAdmin->hasRole('super_admin'))
            return [
                'company' => null,
                'message' => 'unauthorized',
                'code' => 403
            ];
        $user=User::create([
            'email'=>$request['email'],
            'password'=>Hash::make(Str::random(12)),
            'status'=>'accept',
            'email_verified_at'=>now(),
            'is_verified'=>true,
        ]);
        if (isset($request['documents']) && is_array($request['documents'])) {
            foreach ($request['documents'] as $document) {
                if ($document instanceof \Illuminate\Http\UploadedFile) {
                    $url = $document->store('document_images');
                    $user->media()->create(['url' => $url]);
                }
            }
        }
        $user->assignRole('admin');
        $company =$user->adminProfile()->create(['status'=>'فعالة',...$request]);
        return [
            'company' => new AdminResource($company),
            'message' => 'this is all company',
            'code' => 200
        ];
    }
}
