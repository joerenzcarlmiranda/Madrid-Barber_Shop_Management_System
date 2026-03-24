<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class Appointment extends Model
{
    protected $table = 'appointments';

    protected $guarded = [];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public static function calculateEndTime(?string $startTime, ?Service $service): ?string
    {
        if (blank($startTime) || ! $service) {
            return null;
        }

        $durationInMinutes = $service->getDurationInMinutes();

        if (! $durationInMinutes) {
            return null;
        }

        return Carbon::parse($startTime)
            ->addMinutes($durationInMinutes)
            ->format('H:i');
    }

    public static function barberIsAvailable(?int $barberId): bool
    {
        if (blank($barberId)) {
            return false;
        }

        return Barber::query()
            ->whereKey($barberId)
            ->where('is_available', true)
            ->exists();
    }

    public static function getBarberAvailabilityStatus(
        ?int $barberId,
        ?string $appointmentDate,
        ?string $startTime,
        ?Service $service,
        ?int $ignoreAppointmentId = null,
        ?int $ignoreWalkInId = null,
    ): array {
        if (blank($barberId) || blank($appointmentDate) || blank($startTime) || ! $service) {
            return [
                'available' => null,
                'message' => 'Select a barber, service, appointment date, and start time to check availability.',
                'end_time' => null,
            ];
        }

        $calculatedEndTime = static::calculateEndTime($startTime, $service);

        if (blank($calculatedEndTime)) {
            return [
                'available' => false,
                'message' => 'The selected service has an invalid duration.',
                'end_time' => null,
            ];
        }

        $normalizedStartTime = Carbon::parse($startTime)->format('H:i:s');
        $normalizedEndTime = Carbon::parse($calculatedEndTime)->format('H:i:s');

        if (! static::barberIsAvailable($barberId)) {
            return [
                'available' => false,
                'message' => 'This barber is currently marked unavailable.',
                'end_time' => $normalizedEndTime,
            ];
        }

        $scheduleStatus = static::getBarberScheduleStatus(
            $barberId,
            $appointmentDate,
            $normalizedStartTime,
            $normalizedEndTime,
        );

        if (! ($scheduleStatus['available'] ?? false)) {
            return [
                'available' => false,
                'message' => $scheduleStatus['message'],
                'end_time' => $normalizedEndTime,
            ];
        }

        if (static::hasBarberConflict(
            $barberId,
            $appointmentDate,
            $normalizedStartTime,
            $normalizedEndTime,
            $ignoreAppointmentId,
            $ignoreWalkInId,
        )) {
            return [
                'available' => false,
                'message' => 'This barber already has another appointment or walk-in during that time.',
                'end_time' => $normalizedEndTime,
            ];
        }

        return [
            'available' => true,
            'message' => 'Available. This service will end at '.Carbon::parse($normalizedEndTime)->format('g:i A').'.',
            'end_time' => $normalizedEndTime,
        ];
    }

    public static function hasBarberConflict(
        ?int $barberId,
        ?string $appointmentDate,
        ?string $startTime,
        ?string $endTime,
        ?int $ignoreAppointmentId = null,
        ?int $ignoreWalkInId = null,
    ): bool {
        if (blank($barberId) || blank($appointmentDate) || blank($startTime) || blank($endTime)) {
            return false;
        }

        $normalizedStartTime = Carbon::parse($startTime)->format('H:i:s');
        $normalizedEndTime = Carbon::parse($endTime)->format('H:i:s');

        $appointmentConflict = static::query()
            ->when($ignoreAppointmentId, fn (Builder $query) => $query->whereKeyNot($ignoreAppointmentId))
            ->where('barber_id', $barberId)
            ->whereDate('appointment_date', $appointmentDate)
            ->whereNotIn('status', ['cancelled'])
            ->where(fn (Builder $query) => static::applyTimeOverlapConstraint($query, $normalizedStartTime, $normalizedEndTime))
            ->exists();

        if ($appointmentConflict) {
            return true;
        }

        return WalkIn::query()
            ->when($ignoreWalkInId, fn (Builder $query) => $query->whereKeyNot($ignoreWalkInId))
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where('barber_id', $barberId)
            ->whereDate('visit_date', $appointmentDate)
            ->whereNotIn('status', ['cancelled'])
            ->where(fn (Builder $query) => static::applyTimeOverlapConstraint($query, $normalizedStartTime, $normalizedEndTime))
            ->exists();
    }

    public static function applyTimeOverlapConstraint(Builder $query, string $startTime, string $endTime): Builder
    {
        return $query
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);
    }

    public static function getBarberScheduleStatus(
        ?int $barberId,
        ?string $appointmentDate,
        ?string $startTime,
        ?string $endTime,
    ): array {
        if (blank($barberId) || blank($appointmentDate) || blank($startTime) || blank($endTime)) {
            return [
                'available' => false,
                'message' => 'Incomplete appointment timing details.',
            ];
        }

        $barber = Barber::query()
            ->with('schedules')
            ->find($barberId);

        if (! $barber) {
            return [
                'available' => false,
                'message' => 'The selected barber could not be found.',
            ];
        }

        if ($barber->schedules->isEmpty()) {
            return [
                'available' => true,
                'message' => 'No custom weekly schedule is set for this barber yet.',
            ];
        }

        $dayOfWeek = Carbon::parse($appointmentDate)->dayOfWeekIso;
        $dayLabel = BarberSchedule::dayOptions()[$dayOfWeek] ?? 'the selected day';
        $schedule = $barber->schedules->firstWhere('day_of_week', $dayOfWeek);

        if (! $schedule || $schedule->is_day_off) {
            return [
                'available' => false,
                'message' => "This barber does not accept appointments on {$dayLabel}.",
            ];
        }

        if (blank($schedule->start_time) || blank($schedule->end_time)) {
            return [
                'available' => false,
                'message' => "This barber's working hours for {$dayLabel} are incomplete.",
            ];
        }

        $normalizedStartTime = Carbon::parse($startTime)->format('H:i:s');
        $normalizedEndTime = Carbon::parse($endTime)->format('H:i:s');
        $scheduleStartTime = Carbon::parse($schedule->start_time)->format('H:i:s');
        $scheduleEndTime = Carbon::parse($schedule->end_time)->format('H:i:s');

        if ($normalizedEndTime <= $normalizedStartTime) {
            throw new InvalidArgumentException('Appointment end time must be later than the start time.');
        }

        if (($normalizedStartTime < $scheduleStartTime) || ($normalizedEndTime > $scheduleEndTime)) {
            $formattedStartTime = Carbon::parse($scheduleStartTime)->format('g:i A');
            $formattedEndTime = Carbon::parse($scheduleEndTime)->format('g:i A');

            return [
                'available' => false,
                'message' => "This time is outside the barber's working hours for {$dayLabel} ({$formattedStartTime} - {$formattedEndTime}).",
            ];
        }

        return [
            'available' => true,
            'message' => "Within the barber's {$dayLabel} schedule.",
        ];
    }
}
