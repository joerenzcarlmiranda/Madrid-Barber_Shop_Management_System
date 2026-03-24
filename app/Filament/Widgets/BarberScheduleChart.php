<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\User;
use App\Models\WalkIn;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class BarberScheduleChart extends ChartWidget
{
    protected static ?int $sort = 11;

    protected ?string $heading = '7-Day Workload';

    protected ?string $description = 'Your next 7 days of bookings split by appointments and walk-ins.';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isBarber() ?? false;
    }

    protected function getData(): array
    {
        $user = auth()->user();

        if (! $user instanceof User || blank($user->barber_id)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $days = collect(range(0, 6))
            ->map(fn (int $offset) => now()->startOfDay()->copy()->addDays($offset));

        $startDate = $days->first()?->toDateString();
        $endDate = $days->last()?->toDateString();

        $appointments = Appointment::query()
            ->where('barber_id', $user->barber_id)
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->countBy(fn (Appointment $appointment): string => Carbon::parse($appointment->appointment_date)->format('Y-m-d'));

        $walkIns = WalkIn::query()
            ->where('barber_id', $user->barber_id)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->countBy(fn (WalkIn $walkIn): string => Carbon::parse($walkIn->visit_date)->format('Y-m-d'));

        return [
            'datasets' => [
                [
                    'label' => 'Appointments',
                    'data' => $days->map(fn (Carbon $day): int => (int) ($appointments[$day->format('Y-m-d')] ?? 0))->all(),
                    'backgroundColor' => '#f59e0b',
                ],
                [
                    'label' => 'Walk-ins',
                    'data' => $days->map(fn (Carbon $day): int => (int) ($walkIns[$day->format('Y-m-d')] ?? 0))->all(),
                    'backgroundColor' => '#fcd34d',
                ],
            ],
            'labels' => $days->map(fn (Carbon $day): string => $day->format('D, M j'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
