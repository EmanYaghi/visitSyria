<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'post_id', 'place_id', 'city_id', 'event_id','trip_id','article_id', 'url'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }
    public function article()
    {
        return $this->belongsTo(Article::class);
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
