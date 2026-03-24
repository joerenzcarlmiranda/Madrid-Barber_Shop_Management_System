<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalkIn;

class WalkInPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isBarber() && filled($user->barber_id);
    }

    public function view(User $user, WalkIn $walkIn): bool
    {
        return $user->isBarber()
            && filled($user->barber_id)
            && in_array($walkIn->barber_id, [$user->barber_id, null], true);
    }

    public function create(User $user): bool
    {
        return $user->isBarber() && filled($user->barber_id);
    }

    public function update(User $user, WalkIn $walkIn): bool
    {
        return $this->view($user, $walkIn);
    }

    public function delete(User $user, WalkIn $walkIn): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
