<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Profile extends Model
{
    use HasFactory, HasRoles;

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
        'photo',
        'account_status'
    ];
    public static array $gender=['male', 'female', 'other'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
