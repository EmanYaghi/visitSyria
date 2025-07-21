<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'description', 'season', 'start_date','reserved_tickets',
        'duration', 'tickets', 'price', 'discount', 'new_price','status','improvements'
    ];
    public static array $status=['لم تبدأ بعد','منتهية','جارية حاليا','تم الالغاء'];

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
        return $this->hasMany(Timeline::class);
    }
    public function media()
    {
        return $this->hasMany(Media::class);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

