<?php

namespace App\Http\Resources;

use App\Http\Resources\Auth\ProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $user=$this->user;
        $profile=$user->profile;
        $type = $this->trip_id ? 'trip' :'event' ;
        $b=$this->$type;
        return [
            'person'=>[
                'user_id'=>$user->id,
                'email'=>$user->email,
                'profile'=> [
                'id' => $profile->id,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'date_of_birth' => $profile->date_of_birth,
                'gender' => $profile->gender,
                'photo' =>  $profile->photo ? asset('storage/' . $user->profile) : null,
                'country' =>$profile->country,
                'phone' => $profile->phone,
                'country_code' => $profile->country_code,
                ],
            ],

            'passengers' => $this->passengers->map(function ($passenger) {
                return [
                    'first_name' => $passenger->first_name,
                    'last_name' => $passenger->last_name,
                    'gender' => $passenger->gender,
                    'birth_date' => $passenger->birth_date,
                    'nationality' => $passenger->nationality,
                    'email' => $passenger->email,
                    'phone' => $passenger->phone,
                    'country_code' => $passenger->country_code,
                ];
            }),
            'bill'=>[
                'number_of_tickets' => $this->number_of_tickets ?? null,
                'total_price' => $this->price ?? null,
                'price'=>$b->new_price>0?$b->new_price:$b->price,
            ],
        ];
    }
}
