<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'country',
        'phone',
        'country_code',
        'lang',
        'theme_mode',
        'allow_notification',
        'photo'
    ];
    public static array $gender=['male', 'female', 'other'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
