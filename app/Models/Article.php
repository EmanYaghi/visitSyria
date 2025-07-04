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
}
