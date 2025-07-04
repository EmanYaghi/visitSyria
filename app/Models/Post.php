<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'description', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
    public function tags()
    {
        return $this->belongsToMany(TagName::class, 'tags', 'post_id', 'tag_name_id');
    }
    public function saves()
    {
        return $this->hasMany(Save::class);
    }
        public function media()
    {
        return $this->hasMany(Media::class);
    }
}
