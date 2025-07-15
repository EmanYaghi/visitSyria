<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];

    public static array $country_code=['+963','+966','+20','+249','+962','+964','+961','+970','+965','+971','+974','+973','+968','+967','+212','+213','+216','+218','+222','+253','+252','+269'];
    
    public function places()
    {
        return $this->hasMany(Place::class);
    }
    public function  media()
    {
        return $this->hasMany(Media::class);
    }
}
