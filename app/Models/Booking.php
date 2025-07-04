<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'trip_id', 'flight_id', 'event_id', 'number_of_tickets', 
        'number_of_adults', 'number_of_children', 'number_of_infants', 
        'status', 'price', 'payment_method', 'qr_code'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trips()
    {
        return $this->belongsTo(Trip::class);
    }
        public function events()
    {
        return $this->belongsTo(Event::class);
    }

    public function flights()
    {
        return $this->belongsTo(Flight::class);
    }

    public function passengers()
    {
        return $this->hasMany(Passenger::class);
    }

    public function payments()
    {
        return $this->hasOne(Payment::class);
    }

}
