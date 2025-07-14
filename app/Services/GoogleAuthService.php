<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use libphonenumber\PhoneNumberUtil;
use League\ISO3166\ISO3166;

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

        $firstName = $googleUser['given_name'] ?? null;
        $lastName  = $googleUser['family_name'] ?? null;
        $countryCode = strtoupper($data['country_code'] ?? 'SY');

        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $countryPhoneCode = '+' . $phoneUtil->getCountryCodeForRegion($countryCode);
        } catch (\Exception $e) {
            $countryPhoneCode = '+963';
        }

        $iso3166 = new ISO3166();
        try {
            $countryName = $iso3166->alpha2($countryCode)['name'];
        } catch (\Exception $e) {
            $countryName = 'Syria';
        }

        $user = User::firstOrCreate(
            ['email' => $googleUser['email']],
            [
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
                'country_code' => $countryPhoneCode,
                'lang'         => $countryCode,
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
            'country_code' => $countryPhoneCode,
        ];
    }
}
