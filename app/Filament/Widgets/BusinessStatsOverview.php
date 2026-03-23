<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Service;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BusinessStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Business Overview';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Clients', Customer::count())
                ->description('Registered customers')
                ->descriptionColor('info')
                ->icon(Heroicon::OutlinedUsers),
            Stat::make('Total Barbers', Barber::count())
                ->description('Active barber records')
                ->descriptionColor('warning')
                ->icon(Heroicon::OutlinedScissors),
            Stat::make('Total Services', Service::count())
                ->description('Available services')
                ->descriptionColor('success')
                ->icon(Heroicon::OutlinedBriefcase),
            Stat::make('Total Appointments', Appointment::count())
                ->description('All booked appointments')
                ->descriptionColor('primary')
                ->icon(Heroicon::OutlinedCalendarDays),
        ];
    }
}
