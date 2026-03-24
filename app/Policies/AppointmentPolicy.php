<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return ($user->isBarber() && filled($user->barber_id))
            || ($user->isCustomer() && filled($user->customer_id));
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->matchesBarber($appointment->barber_id)
            || $user->matchesCustomer($appointment->customer_id);
    }

    public function create(User $user): bool
    {
        return ($user->isBarber() && filled($user->barber_id))
            || ($user->isCustomer() && filled($user->customer_id));
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->matchesBarber($appointment->barber_id)) {
            return true;
        }

        return $user->matchesCustomer($appointment->customer_id)
            && ($appointment->status !== 'completed');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
