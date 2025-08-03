<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    use HasFactory;

    protected $fillable = [
        'direction', 'airline', 'type', 'departure_airport', 'destination_airport',
        'departure_date', 'departure_time', 'return_date', 'return_time', 'duration',
        'number_of_stopovers', 'number_of_tickets','reserved_tickets', 'price'
    ];
        public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function booking()
    {
        return $this->hasMny(Booking::class);
    }
        public function timeline()
    {
        return $this->hasOne(Timeline::class);
    }
}
