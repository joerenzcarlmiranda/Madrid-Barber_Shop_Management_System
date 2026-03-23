<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WalkIn extends Model
{
    protected $table = 'walk_ins';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (WalkIn $walkIn): void {
            if (blank($walkIn->queue_number)) {
                $walkIn->queue_number = static::generateQueueNumber($walkIn->visit_date ?? now()->toDateString());
            }
        });
    }

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

    public function getDisplayCustomerNameAttribute(): string
    {
        return $this->customer?->full_name
            ?? $this->guest_name
            ?? 'Walk-in Guest';
    }

    public static function generateQueueNumber(string $visitDate): string
    {
        $date = Carbon::parse($visitDate);
        $prefix = 'W-' . $date->format('Ymd') . '-';

        $latestQueueNumber = static::query()
            ->whereDate('visit_date', $date->toDateString())
            ->where('queue_number', 'like', $prefix . '%')
            ->latest('id')
            ->value('queue_number');

        $nextSequence = $latestQueueNumber
            ? ((int) str($latestQueueNumber)->afterLast('-')) + 1
            : 1;

        return $prefix . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
    }

    public static function calculateEndTime(?string $startTime, ?Service $service): ?string
    {
        return Appointment::calculateEndTime($startTime, $service);
    }

    public static function hasBarberConflict(
        ?int $barberId,
        ?string $visitDate,
        ?string $startTime,
        ?string $endTime,
        ?int $ignoreWalkInId = null,
    ): bool {
        if (blank($barberId) || blank($visitDate) || blank($startTime) || blank($endTime)) {
            return false;
        }

        $normalizedStartTime = Carbon::parse($startTime)->format('H:i:s');
        $normalizedEndTime = Carbon::parse($endTime)->format('H:i:s');

        $appointmentConflict = Appointment::query()
            ->where('barber_id', $barberId)
            ->whereDate('appointment_date', $visitDate)
            ->whereNotIn('status', ['cancelled'])
            ->where(function (Builder $query) use ($normalizedStartTime, $normalizedEndTime) {
                $query->whereBetween('start_time', [$normalizedStartTime, $normalizedEndTime])
                    ->orWhereBetween('end_time', [$normalizedStartTime, $normalizedEndTime])
                    ->orWhere(function (Builder $nestedQuery) use ($normalizedStartTime, $normalizedEndTime) {
                        $nestedQuery->where('start_time', '<=', $normalizedStartTime)
                            ->where('end_time', '>=', $normalizedEndTime);
                    });
            })
            ->exists();

        if ($appointmentConflict) {
            return true;
        }

        return static::query()
            ->when($ignoreWalkInId, fn (Builder $query) => $query->whereKeyNot($ignoreWalkInId))
            ->where('barber_id', $barberId)
            ->whereDate('visit_date', $visitDate)
            ->whereNotIn('status', ['cancelled'])
            ->where(function (Builder $query) use ($normalizedStartTime, $normalizedEndTime) {
                $query->whereBetween('start_time', [$normalizedStartTime, $normalizedEndTime])
                    ->orWhereBetween('end_time', [$normalizedStartTime, $normalizedEndTime])
                    ->orWhere(function (Builder $nestedQuery) use ($normalizedStartTime, $normalizedEndTime) {
                        $nestedQuery->where('start_time', '<=', $normalizedStartTime)
                            ->where('end_time', '>=', $normalizedEndTime);
                    });
            })
            ->exists();
    }
}
