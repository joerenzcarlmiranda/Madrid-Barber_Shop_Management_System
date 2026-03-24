<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\User;
use App\Models\WalkIn;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class CustomerSpendingChart extends ChartWidget
{
    protected static ?int $sort = 21;

    protected ?string $heading = 'My Spending Trend';

    protected ?string $description = 'Completed service spending over the last 6 months.';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isCustomer() ?? false;
    }

    protected function getData(): array
    {
        $user = auth()->user();

        if (! $user instanceof User || blank($user->customer_id)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $startDate = now()->startOfMonth()->subMonths(5);
        $months = collect(range(0, 5))
            ->map(fn (int $offset) => $startDate->copy()->addMonths($offset));

        $appointmentSpend = Appointment::query()
            ->with('service:id,price')
            ->where('customer_id', $user->customer_id)
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $startDate->toDateString())
            ->get()
            ->groupBy(fn (Appointment $appointment): string => Carbon::parse($appointment->appointment_date)->format('Y-m'))
            ->map(fn ($appointments): float => (float) $appointments->sum(fn (Appointment $appointment) => (float) ($appointment->service?->price ?? 0)));

        $walkInSpend = WalkIn::query()
            ->with('service:id,price')
            ->where('customer_id', $user->customer_id)
            ->where('status', 'completed')
            ->whereDate('visit_date', '>=', $startDate->toDateString())
            ->get()
            ->groupBy(fn (WalkIn $walkIn): string => Carbon::parse($walkIn->visit_date)->format('Y-m'))
            ->map(fn ($walkIns): float => (float) $walkIns->sum(fn (WalkIn $walkIn) => (float) ($walkIn->service?->price ?? 0)));

        return [
            'datasets' => [
                [
                    'label' => 'Spend',
                    'data' => $months->map(
                        fn (Carbon $month): float => (float) (($appointmentSpend[$month->format('Y-m')] ?? 0) + ($walkInSpend[$month->format('Y-m')] ?? 0)),
                    )->all(),
                    'borderColor' => '#b45309',
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $months->map(fn (Carbon $month): string => $month->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
