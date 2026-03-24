<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\WalkIn;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AppointmentProfitChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Revenue Trend';

    protected ?string $description = 'Monthly revenue based on completed appointments and walk-ins.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getData(): array
    {
        $startDate = now()->startOfMonth()->subMonths(5);
        $months = collect(range(0, 5))
            ->map(fn (int $offset) => $startDate->copy()->addMonths($offset));

        $profitByMonth = Appointment::query()
            ->with('service:id,price')
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $startDate->toDateString())
            ->get()
            ->groupBy(fn (Appointment $appointment) => Carbon::parse($appointment->appointment_date)->format('Y-m'))
            ->map(
                fn ($appointments) => (float) $appointments->sum(
                    fn (Appointment $appointment) => (float) ($appointment->service?->price ?? 0),
                ),
            );

        $walkInProfitByMonth = WalkIn::query()
            ->with('service:id,price')
            ->where('status', 'completed')
            ->whereDate('visit_date', '>=', $startDate->toDateString())
            ->get()
            ->groupBy(fn (WalkIn $walkIn) => Carbon::parse($walkIn->visit_date)->format('Y-m'))
            ->map(
                fn ($walkIns) => (float) $walkIns->sum(
                    fn (WalkIn $walkIn) => (float) ($walkIn->service?->price ?? 0),
                ),
            );

        $labels = $months
            ->map(fn (Carbon $month) => $month->format('M Y'))
            ->all();

        $data = $months
            ->map(
                fn (Carbon $month) => (float) (($profitByMonth[$month->format('Y-m')] ?? 0) + ($walkInProfitByMonth[$month->format('Y-m')] ?? 0)),
            )
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Profit',
                    'data' => $data,
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#b45309',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
