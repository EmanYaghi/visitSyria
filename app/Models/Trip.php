<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'description', 'season', 'start_date', 
        'duration', 'tickets', 'price', 'discount', 'new_price'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function suggestions()
    {
        return $this->hasMany(Suggestion::class);
    }

    public function saves()
    {
        return $this->hasMany(Save::class);
    }
    
    public function tags()
    {
        return $this->hasMany(Tag::class);
    }
    
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
   public function timelines()
    {
        return $this->hasOne(Timeline::class);
    }
}

