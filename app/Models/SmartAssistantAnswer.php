<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartAssistantAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type_of_trips', 'duration', 'average_activity', 
        'travel_with', 'sleeping_in_hotel', 'type_of_places', 'cities'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
        public function suggestions()
    {
        return $this->hasMany(Suggestion::class);
    }
}
