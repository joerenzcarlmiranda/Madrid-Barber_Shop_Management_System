<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\User;

class LinkedUserAccountManager
{
    public function syncForBarber(Barber $barber, ?string $password = null): User
    {
        $user = $barber->user()->firstOrNew();

        return $this->sync(
            $user,
            [
                'name' => $barber->full_name,
                'email' => $barber->email,
                'role' => UserRole::BARBER,
                'barber_id' => $barber->getKey(),
                'customer_id' => null,
            ],
            $password,
        );
    }

    public function syncForCustomer(Customer $customer, ?string $password = null): User
    {
        $user = $customer->user()->firstOrNew();

        return $this->sync(
            $user,
            [
                'name' => $customer->full_name,
                'email' => $customer->email,
                'role' => UserRole::CUSTOMER,
                'barber_id' => null,
                'customer_id' => $customer->getKey(),
            ],
            $password,
        );
    }

    protected function sync(User $user, array $attributes, ?string $password = null): User
    {
        $user->fill($attributes);

        if (filled($password)) {
            $user->password = $password;
        }

        $user->save();

        return $user;
    }
}
