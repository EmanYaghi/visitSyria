<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id', 'name', 'description', 'longitude', 'latitude', 'date', 
        'duration', 'place', 'tickets', 'price'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

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
