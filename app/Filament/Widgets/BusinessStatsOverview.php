<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FormatsCurrency;
use App\Models\Appointment;
use App\Models\WalkIn;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BusinessStatsOverview extends StatsOverviewWidget
{
    use FormatsCurrency;

    protected static ?int $sort = 1;

    protected ?string $heading = 'Business Overview';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();

        $completedAppointmentsThisMonth = Appointment::query()
            ->with('service:id,price')
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $monthStart)
            ->get();

        $completedWalkInsThisMonth = WalkIn::query()
            ->with('service:id,price')
            ->where('status', 'completed')
            ->whereDate('visit_date', '>=', $monthStart)
            ->get();

        $monthRevenue = $completedAppointmentsThisMonth->sum(
            fn (Appointment $appointment): float => (float) ($appointment->service?->price ?? 0),
        ) + $completedWalkInsThisMonth->sum(
            fn (WalkIn $walkIn): float => (float) ($walkIn->service?->price ?? 0),
        );

        $todayAppointments = Appointment::query()
            ->whereDate('appointment_date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $activeWalkIns = WalkIn::query()
            ->whereDate('visit_date', $today)
            ->whereIn('status', ['waiting', 'called', 'in_service'])
            ->count();

        $pendingAppointments = Appointment::query()
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('Month Revenue', $this->formatCurrency($monthRevenue))
                ->description('Completed appointments and walk-ins this month')
                ->descriptionColor('success')
                ->icon(Heroicon::OutlinedBanknotes),
            Stat::make('Today\'s Appointments', $todayAppointments)
                ->description('Booked chairs for today')
                ->descriptionColor('info')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make('Active Walk-ins', $activeWalkIns)
                ->description('Waiting, called, or in service today')
                ->descriptionColor('warning')
                ->icon(Heroicon::OutlinedQueueList),
            Stat::make('Pending Requests', $pendingAppointments)
                ->description('Appointments awaiting confirmation')
                ->descriptionColor('primary')
                ->icon(Heroicon::OutlinedClock),
        ];
    }
}
