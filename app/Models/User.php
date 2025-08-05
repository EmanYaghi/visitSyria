<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class User extends Authenticatable implements JWTSubject,MustVerifyEmail
{
    use HasFactory, Notifiable,HasRoles;

    public static array $status=['accept','reject','on hold'];

    protected $fillable = [
        'email',
        'password',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    public function adminProfile()
    {
        return $this->hasOne(AdminProfile::class);
    }
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    public function supports()
    {
        return $this->hasOne(Support::class);
    }
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function saves()
    {
        return $this->hasMany(Save::class);
    }
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function creditcards()
    {
        return $this->hasMany(CreditCard::class);
    }
    public function media()
    {
        return $this->hasOne(Media::class);
    }
    public function tags()
    {
        return $this->hasMany(Tag::class);
    }
    public function preference()
    {
        return $this->hasOne(Preference::class);
    }
    public function smart_assistant_answers()
    {
        return $this->hasMany(SmartAssistantAnswer::class);
    }
    public function flights()
    {
        return $this->hasMany(Flight::class);
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function creditCard()
    {
        return $this->hasOne(CreditCard::class);
    }

     public function fcnTokens()
    {
        return $this->hasMany(FcmToken::class);
    }

}
