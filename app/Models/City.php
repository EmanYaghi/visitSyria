<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];

    public function places()
    {
        return $this->hasMany(Place::class);
    }
    public function  media()
    {
        return $this->hasMany(Media::class);
    }
}
