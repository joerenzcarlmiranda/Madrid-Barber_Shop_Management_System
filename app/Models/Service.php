<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $table = 'services';
    protected $guarded = [];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function walkIns(): HasMany
    {
        return $this->hasMany(WalkIn::class);
    }

    public function getDurationInMinutes(): ?int
    {
        if (blank($this->duration)) {
            return null;
        }

        $normalizedDuration = strtolower(trim($this->duration));

        if (preg_match('/^\d+$/', $normalizedDuration)) {
            return (int) $normalizedDuration;
        }

        $minutes = 0;

        if (preg_match_all('/(\d+)\s*(hour|hours|hr|hrs|h|minute|minutes|min|mins|m)/', $normalizedDuration, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $value = (int) $match[1];
                $unit = $match[2];

                if (in_array($unit, ['hour', 'hours', 'hr', 'hrs', 'h'], true)) {
                    $minutes += $value * 60;
                } else {
                    $minutes += $value;
                }
            }
        }

        return $minutes > 0 ? $minutes : null;
    }
}
