<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'flight_id', 'day_number'];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }
    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
