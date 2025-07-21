<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TripPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, Trip $trip): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }
    public function update(User $user, Trip $trip)
    {
        return $user->hasRole('admin') && $trip->user_id === $user->id;
    }
    public function delete(User $user, Trip $trip): bool
    {
        return $user->hasRole('admin') && $trip->user_id === $user->id;
    }
    public function restore(User $user, Trip $trip): bool
    {
        return true;
    }
    public function forceDelete(User $user, Trip $trip): bool
    {
        return true;
    }
}
