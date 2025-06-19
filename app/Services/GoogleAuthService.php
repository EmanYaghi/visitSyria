<?php

namespace App\Services;

use App\Models\User;
use Google_Client;
use Illuminate\Support\Facades\Http; 
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
class GoogleAuthService
{
    public function authenticate(array $data): array
    {        

    $googleResponse = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $data['id_token'],
        ]);

        if ($googleResponse->failed() || !$googleResponse->json('email')) {
            return [
                'code'    => 401,
                'message' => 'Invalid Google token',
            ];
        }
        $googleUser = $googleResponse->json();

        $user = User::firstOrCreate(
            ['email' => $googleUser['email']],
            [
                'name'              => $googleUser['name'] ?? $googleUser['email'],
                'password'          => bcrypt(Str::random(16)),
                'email_verified_at' => now(),
            ]
        );

        $token = JWTAuth::fromUser($user);

        return [
            'code'         => 200,
            'message'      => 'Logged in with Google successfully',
            'token'        => $token,
            'user'         => $user,
        ];
}
}