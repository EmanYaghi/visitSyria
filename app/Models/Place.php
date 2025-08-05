<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;
        protected $fillable = [
        'city_id',
        'type',
        'name',
        'description',
        'number_of_branches',
        'phone',
        'country_code',
        'place',
        'longitude',
        'latitude',
        'rating',
        'classification'
        ];
    public function getIdAttribute($value)
    {
        return str_pad($value, 6, '0', STR_PAD_LEFT);
    }
    
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function tags()
    {
        return $this->hasMany(Tag::class);
    }
    public function saves()
    {
        return $this->hasMany(Save::class);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
    public function media()
    {
        return $this->hasMany(Media::class);
    }
}

