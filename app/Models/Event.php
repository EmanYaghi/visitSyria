<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'longitude',
        'latitude',
        'place',
        'date',
        'duration_days',
        'duration_hours',
        'tickets',
        'price',
        'event_type',
        'price_type',
        'pre_booking'
    ];
    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function saves()
    {
        return $this->hasMany(Save::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
