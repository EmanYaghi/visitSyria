<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable=['timeline_id', 'time', 'title', 'description'];
    public function timeline()
    {
        return $this->belongsTo(Timeline::class);
    }
}
