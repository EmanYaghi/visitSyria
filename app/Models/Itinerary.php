<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Itinerary extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'request_payload',
        'timelines',
        'raw_response',
        'model',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'timelines' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
