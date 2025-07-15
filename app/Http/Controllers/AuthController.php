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

    public function logout()
    {
        return $this->handle(fn() => $this->authService->logout());
    }

    public function deleteAccount(DeleteAccountRequest $request)
    {
        return $this->handle(fn() => $this->authService->deleteAccount($request->validated()));
    }

    public function setPreference(CreatePrefereneRequest $request)
    {
        return $this->handle(fn() => $this->authService->setPreference($request->validated()));
    }

    public function updatePreference(UpdatePrefereneRequest $request)
    {
        return $this->handle(fn() => $this->authService->updatePreference($request->validated()));
    }

    public function setProfile(CreateProfileRequest $request)
    {
        return $this->handle(function () use ($request) {
            $data = $request->validated();
            if ($request->hasFile('image')) {
                $data['photo'] = $request->file('image')->store('profile_photos', 'public');
            } elseif (isset($data['photo']) && is_array($data['photo'])) {
                $data['photo'] = $data['photo']['uuid'] ?? null;
            }
            $request->merge(['photo' => $data['photo'] ?? null]);
            return $this->authService->setProfile($request);
        });
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        return $this->handle(function () use ($request) {
            $data = $request->validated();
            if ($request->hasFile('image')) {
                $data['photo'] = $request->file('image')->store('profile_photos', 'public');
            } elseif (isset($data['photo']) && is_array($data['photo'])) {
                $data['photo'] = $data['photo']['uuid'] ?? null;
            }
            $request->merge(['photo' => $data['photo'] ?? null]);
            return $this->authService->updateProfile($request);
        });
    }

    public function getProfile()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not found.', 'code' => 404], 404);
        }

        return response()->json([
            'message' => 'User found',
            'profile' => new ProfileResource($user->profile),
        ], 200);
    }

    public function setAdminProfile(CreateAdminProfileRequest $request)
    {
        return $this->handle(fn() => $this->authService->setAdminProfile($request->validated()));
    }

    public function updateAdminProfile(UpdateAdminProfileRequest $request)
    {
        return $this->handle(fn() => $this->authService->updateAdminProfile($request->validated()));
    }

    public function getAdminProfile()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not found.', 'code' => 404], 404);
        }

        return response()->json([
            'message' => 'User found',
            'profile' => new AdminProfileResource($user->adminProfile),
        ], 200);
    }
}
