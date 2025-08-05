<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stripe_payment_method_id',
        'card_holder',
        'brand',
        'last4',
        'exp_month',
        'exp_year',
        'is_default'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
        public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
