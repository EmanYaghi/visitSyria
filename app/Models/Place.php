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

    protected $appends = ['formatted_id'];

    public function getFormattedIdAttribute(): ?string
    {
        $raw = $this->getRawOriginal($this->getKeyName()) ?? $this->attributes[$this->getKeyName()] ?? null;
        if ($raw === null) return null;
        return str_pad((int)$raw, 6, '0', STR_PAD_LEFT);
    }

    public function getKey()
    {
        $raw = $this->getRawOriginal($this->getKeyName());
        if ($raw !== null) {
            return $raw;
        }
        return parent::getKey();
    }

    public function latestComments()
    {
        return $this->hasMany(Comment::class)->latest();
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
