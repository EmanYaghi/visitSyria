<?php
namespace App\Services;

use App\Models\User;
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

        if ($googleResponse->failed() || ! $googleResponse->json('email')) {
            return [
                'code'    => 401,
                'message' => 'Invalid Google token',
            ];
        }

        $googleUser = $googleResponse->json();

        $firstName = $googleUser['given_name']  ?? null;
        $lastName  = $googleUser['family_name'] ?? null;
        $locale = $googleUser['locale'] ?? 'ar-SY';

        if (str_contains($locale, '-')) {
            [, $countryCode] = explode('-', $locale, 2);
        } else {
            $countryCode = $locale;
        }

        $countryList = include base_path('vendor/umpirsky/country-list/data/en/country.php');
        $countryCode = strtoupper($countryCode);
        $countryName = $countryList[$countryCode] ?? $countryCode;

        $user = User::firstOrCreate(
            ['email' => $googleUser['email']],
            [
                'name'              => $googleUser['name'] ?? $googleUser['email'],
                'password'          => bcrypt(Str::random(16)),
                'email_verified_at' => now(),
            ]
        );

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'country'      => $countryName,
                'country_code' => $countryCode,
            ]
        );

        $token = JWTAuth::fromUser($user);

        return [
            'code'         => 200,
            'message'      => 'Logged in with Google successfully',
            'token'        => $token,
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'country'      => $countryName,
            'country_code' => $countryCode,
        ];
    }
}
