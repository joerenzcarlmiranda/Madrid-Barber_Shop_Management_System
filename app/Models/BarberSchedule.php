<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class BarberSchedule extends Model
{
    protected $table = 'barber_schedules';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_day_off' => 'boolean',
        ];
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public static function dayOptions(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    public function getDayLabelAttribute(): string
    {
        return static::dayOptions()[$this->day_of_week] ?? 'Unknown day';
    }

    public function getFormattedHoursAttribute(): string
    {
        if ($this->is_day_off) {
            return 'Day off';
        }

        if (blank($this->start_time) || blank($this->end_time)) {
            return 'Hours not set';
        }

        return Carbon::parse($this->start_time)->format('g:i A')
            .' - '
            .Carbon::parse($this->end_time)->format('g:i A');
    }
}
