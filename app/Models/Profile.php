<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
use Carbon\Carbon;

class Profile extends Model
{
    use HasFactory, HasRoles;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'country',
        'phone',
        'country_code',
        'lang',
        'theme_mode',
        'allow_notification',
        'photo',
        'account_status',
        'date_of_unblock',
    ];

    protected $casts = [
        'date_of_unblock' => 'datetime',
    ];

    public static array $gender = ['male', 'female', 'other'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function refreshIfUnblocked(): bool
    {
        if (empty($this->date_of_unblock)) {
            return false;
        }

        $unblockAt = $this->date_of_unblock instanceof Carbon
            ? $this->date_of_unblock
            : Carbon::parse($this->date_of_unblock);

        if ($unblockAt->lte(now())) {
            $this->account_status = 'نشط';
            $this->date_of_unblock = null;
            $this->save();
            return true;
        }

        return false;
    }

    public function remainingUnblockInterval(): ?array
    {
        if (empty($this->date_of_unblock)) {
            return null;
        }

        $unblockAt = $this->date_of_unblock instanceof Carbon
            ? $this->date_of_unblock
            : Carbon::parse($this->date_of_unblock);

        if ($unblockAt->lte(now())) {
            $this->account_status = 'نشط';
            $this->date_of_unblock = null;
            $this->save();
            return null;
        }

        $diff = $unblockAt->diff(now());

        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'seconds' => $diff->s,
        ];
    }

}
