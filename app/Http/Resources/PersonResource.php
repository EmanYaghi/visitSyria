<?php

namespace App\Http\Resources;

use App\Http\Resources\Auth\ProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'user_id'=>$this->id,
            'email'=>$this->email,
            'profile'=> [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'photo' =>  $this->photo ? asset('storage/' . $this->photo) : null,
            'country' => $this->country,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'lang' => $this->lang,
            'theme_mode' => $this->theme_mode,
            'allow_notification' => $this->allow_notification,
            ],
        ];
    }
}
