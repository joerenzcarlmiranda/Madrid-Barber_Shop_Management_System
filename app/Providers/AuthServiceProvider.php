<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Service;
use App\Models\WalkIn;
use App\Policies\AppointmentPolicy;
use App\Policies\BarberPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ServicePolicy;
use App\Policies\WalkInPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Appointment::class => AppointmentPolicy::class,
        Barber::class => BarberPolicy::class,
        Customer::class => CustomerPolicy::class,
        Service::class => ServicePolicy::class,
        WalkIn::class => WalkInPolicy::class,
    ];
}
