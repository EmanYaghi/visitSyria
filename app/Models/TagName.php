<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagName extends Model
{
    protected $table = 'tag_names';
    protected $fillable = ['body', 'follow_to'];

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }
}