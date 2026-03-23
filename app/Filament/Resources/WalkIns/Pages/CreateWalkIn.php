<?php

namespace App\Filament\Resources\WalkIns\Pages;

use App\Filament\Resources\WalkIns\WalkInResource;
use App\Models\Service;
use App\Models\WalkIn;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateWalkIn extends CreateRecord
{
    protected static string $resource = WalkInResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = Service::find($data['service_id']);
        $data['queue_number'] = WalkIn::generateQueueNumber($data['visit_date']);

        if (blank($data['start_time'] ?? null)) {
            $data['end_time'] = null;

            return $data;
        }

        $calculatedEndTime = WalkIn::calculateEndTime($data['start_time'], $service);

        if (blank($calculatedEndTime)) {
            throw ValidationException::withMessages([
                'service_id' => 'The selected service has an invalid duration.',
            ]);
        }

        $data['start_time'] = Carbon::parse($data['start_time'])->format('H:i:s');
        $data['end_time'] = Carbon::parse($calculatedEndTime)->format('H:i:s');

        if (WalkIn::hasBarberConflict($data['barber_id'] ?? null, $data['visit_date'], $data['start_time'], $data['end_time'])) {
            throw ValidationException::withMessages([
                'start_time' => 'This barber already has another appointment or walk-in during that time.',
            ]);
        }

        return $data;
    }
}
