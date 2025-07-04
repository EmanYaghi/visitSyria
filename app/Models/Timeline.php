<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'flight_id', 'day_number', 'time', 'title', 'description', 'sort_order'];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }
}
