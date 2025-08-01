<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditCard extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','stripe_payment_method_id', 'card_holder', 'card_number', 'cvc', 'expiry_date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
        public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
