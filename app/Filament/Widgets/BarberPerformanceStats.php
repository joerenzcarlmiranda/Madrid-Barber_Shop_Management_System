<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FormatsCurrency;
use App\Models\Appointment;
use App\Models\User;
use App\Models\WalkIn;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BarberPerformanceStats extends StatsOverviewWidget
{
    use FormatsCurrency;

    protected static ?int $sort = 10;

    protected ?string $heading = 'My Chair Snapshot';

    public static function canView(): bool
    {
        return auth()->user()?->isBarber() ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User || blank($user->barber_id)) {
            return [];
        }

        $today = now()->toDateString();

        $todayAppointments = Appointment::query()
            ->where('barber_id', $user->barber_id)
            ->whereDate('appointment_date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $todayWalkIns = WalkIn::query()
            ->where('barber_id', $user->barber_id)
            ->whereDate('visit_date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $completedAppointments = Appointment::query()
            ->with('service:id,price')
            ->where('barber_id', $user->barber_id)
            ->whereDate('appointment_date', $today)
            ->where('status', 'completed')
            ->get();

        $completedWalkIns = WalkIn::query()
            ->with('service:id,price')
            ->where('barber_id', $user->barber_id)
            ->whereDate('visit_date', $today)
            ->where('status', 'completed')
            ->get();

        $completedToday = $completedAppointments->count() + $completedWalkIns->count();
        $earningsToday = $completedAppointments->sum(fn (Appointment $appointment) => (float) ($appointment->service?->price ?? 0))
            + $completedWalkIns->sum(fn (WalkIn $walkIn) => (float) ($walkIn->service?->price ?? 0));

        return [
            Stat::make('Today\'s Appointments', $todayAppointments)
                ->description('Booked clients on your schedule')
                ->descriptionColor('info')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make('Today\'s Walk-ins', $todayWalkIns)
                ->description('Queue entries assigned to you')
                ->descriptionColor('warning')
                ->icon(Heroicon::OutlinedQueueList),
            Stat::make('Completed Today', $completedToday)
                ->description('Finished services for the day')
                ->descriptionColor('success')
                ->icon(Heroicon::OutlinedCheckBadge),
            Stat::make('Estimated Earnings', $this->formatCurrency($earningsToday))
                ->description('Completed work value today')
                ->descriptionColor('primary')
                ->icon(Heroicon::OutlinedBanknotes),
        ];
    }
}
