<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FormatsCurrency;
use App\Models\Appointment;
use App\Models\User;
use App\Models\WalkIn;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerBookingStats extends StatsOverviewWidget
{
    use FormatsCurrency;

    protected static ?int $sort = 20;

    protected ?string $heading = 'My Business With The Shop';

    public static function canView(): bool
    {
        return auth()->user()?->isCustomer() ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User || blank($user->customer_id)) {
            return [];
        }

        $today = now()->toDateString();

        $upcomingAppointments = Appointment::query()
            ->where('customer_id', $user->customer_id)
            ->whereDate('appointment_date', '>=', $today)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        $completedAppointments = Appointment::query()
            ->with('service:id,price')
            ->where('customer_id', $user->customer_id)
            ->where('status', 'completed')
            ->get();

        $completedWalkIns = WalkIn::query()
            ->with('service:id,price')
            ->where('customer_id', $user->customer_id)
            ->where('status', 'completed')
            ->get();

        $completedVisits = $completedAppointments->count() + $completedWalkIns->count();
        $totalSpent = $completedAppointments->sum(fn (Appointment $appointment) => (float) ($appointment->service?->price ?? 0))
            + $completedWalkIns->sum(fn (WalkIn $walkIn) => (float) ($walkIn->service?->price ?? 0));

        $cancelledBookings = Appointment::query()
            ->where('customer_id', $user->customer_id)
            ->where('status', 'cancelled')
            ->count();

        return [
            Stat::make('Upcoming Appointments', $upcomingAppointments)
                ->description('Pending or confirmed visits ahead')
                ->descriptionColor('info')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make('Completed Visits', $completedVisits)
                ->description('Appointments and walk-ins served')
                ->descriptionColor('success')
                ->icon(Heroicon::OutlinedCheckBadge),
            Stat::make('Total Spent', $this->formatCurrency($totalSpent))
                ->description('Value of completed services')
                ->descriptionColor('warning')
                ->icon(Heroicon::OutlinedBanknotes),
            Stat::make('Cancelled Bookings', $cancelledBookings)
                ->description('Appointments you cancelled')
                ->descriptionColor('danger')
                ->icon(Heroicon::OutlinedClock),
        ];
    }
}
