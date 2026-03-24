<?php

namespace App\Policies;

use App\Models\Barber;
use App\Models\User;

class BarberPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isBarber() || $user->isCustomer();
    }

    public function view(User $user, Barber $barber): bool
    {
        if ($user->isCustomer()) {
            return true;
        }

        return $user->matchesBarber($barber);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Barber $barber): bool
    {
        return $user->matchesBarber($barber);
    }

    public function delete(User $user, Barber $barber): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
