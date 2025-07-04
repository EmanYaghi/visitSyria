<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    use HasFactory;

    protected $fillable = ['smart_assistant_answer_id', 'trip_id'];

    public function smartAssistantAnswer()
    {
        return $this->belongsTo(SmartAssistantAnswer::class);
    }
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
