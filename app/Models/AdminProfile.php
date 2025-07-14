<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminProfile extends Model
{
    protected $fillable = [
        'user_id',
        'name_of_company',
        'name_of_owner',
        'founding_date',
        'license_number',
        'phone',
        'country_code',
        'description',
        'location',
        'latitude',
        'longitude',
        'status',
        'number_of_trips',
        'rating',
        'photo'
    ];
     public function user()
    {
        return $this->belongsTo(User::class);
    }
}
