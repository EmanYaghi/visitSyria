<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

use App\Http\Requests\Auth\{
    ChangePasswordRequest,
    CreateAdminProfileRequest,
    CreatePrefereneRequest,
    CreateProfileRequest,
    DeleteAccountRequest,
    LoginRequest,
    LoginWithGoogleRequest,
    RegisterCompanyRequest,
    RegisterRequest,
    ResetPasswordRequest,
    SendCodeRequest,
    UpdateAdminProfileRequest,
    UpdatePrefereneRequest,
    UpdateProfileRequest,
    VerifyCodeRequest
};
use App\Http\Resources\Auth\{
    AdminProfileResource,
    PreferenceResource,
    ProfileResource,
};
use App\Services\AuthService;
use App\Services\GoogleAuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Throwable;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected GoogleAuthService $googleAuthService;

    public function __construct(
        AuthService $authService,
        GoogleAuthService $googleAuthService
    ) {
        $this->authService = $authService;
        $this->googleAuthService = $googleAuthService;
    }

    private function handle(callable $action, int $defaultCode = 200): \Illuminate\Http\JsonResponse
    {
        try {
            $data = $action();
            $status = $data['code'] ?? $defaultCode;
            unset($data['code']);
            return response()->json($data, $status);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Server Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function register(RegisterRequest $request)
    {
        return $this->handle(fn() => $this->authService->register($request->validated()));
    }

    public function verifyEmail(VerifyCodeRequest $request)
    {
        return $this->handle(fn() => $this->authService->verifyEmail($request->validated()));
    }

    public function verifyCode(VerifyCodeRequest $request)
    {
        return $this->handle(fn() => $this->authService->verifyCode($request->validated()));
    }

    public function resendVerificationCode(SendCodeRequest $request)
    {
        return $this->handle(fn() => $this->authService->resendVerificationCode($request->validated()));
    }

    public function forgetPassword(SendCodeRequest $request)
    {
        return $this->handle(fn() => $this->authService->forgetPassword($request->validated()));
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->handle(fn() => $this->authService->resetPassword($request->validated()));
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        return $this->handle(fn() => $this->authService->changePassword($request->validated()));
    }

    public function login(LoginRequest $request)
    {
        return $this->handle(fn() => $this->authService->login($request->validated()));
    }

    public function loginWithGoogle(LoginWithGoogleRequest $request)
    {
        return $this->handle(fn() => $this->googleAuthService->authenticate($request->validated()));
    }

    public function logout(Request $request)
    {
        return $this->handle(fn() => $this->authService->logout($request));
    }

    public function deleteAccount(DeleteAccountRequest $request)
    {
        return $this->handle(fn() => $this->authService->deleteAccount($request->validated()));
    }

     public function setPreference(CreatePrefereneRequest $request)
    {
         return $this->handle(fn() => $this->authService->setPreference(
            $request->validated()
        ));
    }
    public function setProfile(CreateProfileRequest $request)
    {
        if(Auth::user()->profile)
            return response()->json(["message"=>" the profile already exists please use update request to modify it"]);
        $data= $request->validated();
        if ($request->hasFile('photo')) {
            $data['photo']=str::random(32).".".$request->photo->getClientOriginalExtension();
            Storage::disk('public')->put($data['photo'],file_get_contents($request->photo));
        }
        return $this->handle(fn() => $this->authService->setProfile(
           $data
        ));
    }
    public function updateProfile(UpdateProfileRequest $request)
    {
        $data= $request->validated();
        if ($request->hasFile('photo')) {
            $data['photo']=str::random(32).".".$request->photo->getClientOriginalExtension();
            Storage::disk('public')->put($data['photo'],file_get_contents($request->photo));
        }
        return $this->handle(fn() => $this->authService->updateProfile(
            $data
        ));
    }
     public function getProfile()
    {
        $profile=null;
        $user = Auth::user();
        if (!$user) {
            $message='User not found.';
            $code=404;
        }
        else{
            $message= 'user founded';
            $code=200;
            $profile=$user->profile;
        }
        return response()->json(['message'=>$message,"me"=>new ProfileResource($profile)],$code);
    }
     public function setAdminProfile(CreateAdminProfileRequest $request)
    {
        if(Auth::user()->adminProfile)
            return response()->json(["message"=>" the profile already exists please use update request to modify it"]);

        $data= $request->validated();
        if ($request->hasFile('image')) {
            $data['image']=str::random(32).".".$request->image->getClientOriginalExtension();
            Storage::disk('public')->put($data['image'],file_get_contents($request->image));
        }
        return $this->handle(fn() => $this->authService->setAdminProfile(
            $data
        ));
    }
     public function updateAdminProfile(UpdateAdminProfileRequest $request)
    {
       $data= $request->validated();
        if ($request->hasFile('image')) {
            $data['image']=str::random(32).".".$request->image->getClientOriginalExtension();
            Storage::disk('public')->put($data['image'],file_get_contents($request->image));
        }
        return $this->handle(fn() => $this->authService->updateAdminProfile(
            $data
        ));
    }
    public function getAdminProfile()
    {
        $user = Auth::user();
        if (!$user) {
            $message= 'User not found.';
            $code=404;
        }
        else{
            $message= 'user founded';
            $code=200;
        }
        return [
            'message'=>$message,
            'code'=>$code,
            'profile'=>new AdminProfileResource($user->adminProfile),
        ];
    }




     public function registerCompanyBySuperAdmin(RegisterCompanyRequest $request)
    {
        $data= $request->validated();
        if ($request->hasFile('image')) {
            $data['image']=str::random(32).".".$request->image->getClientOriginalExtension();
            Storage::disk('public')->put($data['image'],file_get_contents($request->image));
        }
        $data['documents'] = $request->file('documents');
        return $this->handle(fn() => $this->authService->registerCompanyBySuperAdmin(
            $data
        ));
    }

    public function getRole()
    {
        $user=Auth::user();
        return response()->json(['role'=>$user->getRoleNames()->first() ]);
    }

}
