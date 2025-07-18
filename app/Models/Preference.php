<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'preferred_season',
        'preferred_activities',
        'duration',
        'cities'
    ];
    protected $casts=[
        'preferred_season'=>'array',
        'preferred_activities'=>'array',
        'duration'=>'array',
        'cities'=>'array'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
   
}
