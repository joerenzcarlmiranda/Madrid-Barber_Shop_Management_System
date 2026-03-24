<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\WalkIn;
use Filament\Widgets\ChartWidget;

class AdminServiceDemandChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Top Services This Month';

    protected ?string $description = 'Most-booked services across appointments and walk-ins.';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getData(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();

        $appointmentCounts = Appointment::query()
            ->with('service:id,name')
            ->whereDate('appointment_date', '>=', $monthStart)
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->countBy(fn (Appointment $appointment): string => $appointment->service?->name ?? 'Unknown Service');

        $walkInCounts = WalkIn::query()
            ->with('service:id,name')
            ->whereDate('visit_date', '>=', $monthStart)
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->countBy(fn (WalkIn $walkIn): string => $walkIn->service?->name ?? 'Unknown Service');

        $topServices = $appointmentCounts
            ->mergeRecursive($walkInCounts)
            ->map(fn ($value): int => (int) collect($value)->flatten()->sum())
            ->sortDesc()
            ->take(5);

        if ($topServices->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Bookings',
                        'data' => [0],
                        'backgroundColor' => ['#f59e0b'],
                    ],
                ],
                'labels' => ['No bookings yet'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => $topServices->values()->all(),
                    'backgroundColor' => [
                        '#f59e0b',
                        '#d97706',
                        '#b45309',
                        '#92400e',
                        '#78350f',
                    ],
                ],
            ],
            'labels' => $topServices->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
