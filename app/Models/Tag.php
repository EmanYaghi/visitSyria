<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id', 'article_id', 'trip_id', 'place_id', 'user_id', 'tag_name_id'
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tagName()
    {
        return $this->belongsTo(TagName::class);
    }
}
