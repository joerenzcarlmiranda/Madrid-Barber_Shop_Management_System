<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isBarber() || $user->isCustomer();
    }

    public function view(User $user, Service $service): bool
    {
        return $user->isBarber() || $user->isCustomer();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Service $service): bool
    {
        return false;
    }

    public function delete(User $user, Service $service): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
