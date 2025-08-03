<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'first_name', 'last_name', 'gender',
        'birth_date', 'nationality', 'email', 'phone', 'country_code',
        'passport_number','passport_expiry_date'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
