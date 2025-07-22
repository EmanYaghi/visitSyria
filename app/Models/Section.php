<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable=['timeline_id', 'time', 'title', 'description','longitude','latitude'];
     protected $casts=[
        'description'=>'array',
    ];
    public function timeline()
    {
        return $this->belongsTo(Timeline::class);
    }
}
