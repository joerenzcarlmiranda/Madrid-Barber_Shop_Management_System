<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
}
