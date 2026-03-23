<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AppointmentProfitChart extends ChartWidget
{
    protected ?string $heading = 'Completed Appointment Profit';

    protected ?string $description = 'Monthly revenue based on completed appointments.';

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

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

        $labels = $months
            ->map(fn (Carbon $month) => $month->format('M Y'))
            ->all();

        $data = $months
            ->map(fn (Carbon $month) => (float) ($profitByMonth[$month->format('Y-m')] ?? 0))
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
