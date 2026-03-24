<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Barber extends Model
{
    protected $table = 'barbers';

    protected $guarded = [];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function walkIns(): HasMany
    {
        return $this->hasMany(WalkIn::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(BarberSchedule::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return collect([
            $this->firstname,
            $this->middlename,
            $this->lastname,
        ])->filter()->implode(' ');
    }

    public function getWeeklyScheduleSummaryAttribute(): array
    {
        return $this->schedules
            ->sortBy('day_of_week')
            ->map(
                fn (BarberSchedule $schedule): string => $schedule->day_label.': '.$schedule->formatted_hours,
            )
            ->values()
            ->all();
    }

    public function getScheduleForDate(string $date): ?BarberSchedule
    {
        return $this->schedules
            ->firstWhere('day_of_week', Carbon::parse($date)->dayOfWeekIso);
    }
}
