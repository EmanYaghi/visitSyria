<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'body'];

    public function saves()
    {
        return $this->hasMany(Save::class);
    }
        public function tags()
    {
        return $this->hasMany(Tag::class);
    }
        public function media()
    {
        return $this->hasOne(Media::class);
    }
    public function getImageUrlAttribute()
    {
        if (!$this->relationLoaded('media')) {
            $this->load('media');
        }

        return $this->media?->url ?? null;
    }
}
