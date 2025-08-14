<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'trip_id', 'event_id', 'number_of_tickets',
        'number_of_adults', 'number_of_children', 'number_of_infants',
        'is_paid', 'price', 'stripe_payment_id', 'qr_code','payment_status',
        'flight_data','flightOrderId'
    ];
    protected $casts = [
        'flight_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
        public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function passengers()
    {
        return $this->hasMany(Passenger::class);
    }

}
