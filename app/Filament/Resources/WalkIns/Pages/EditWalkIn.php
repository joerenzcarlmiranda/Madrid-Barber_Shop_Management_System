<?php

namespace App\Filament\Resources\WalkIns\Pages;

use App\Filament\Resources\WalkIns\WalkInResource;
use App\Models\Service;
use App\Models\User;
use App\Models\WalkIn;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditWalkIn extends EditRecord
{
    protected static string $resource = WalkInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if ($user instanceof User && $user->isBarber()) {
            $data['barber_id'] = $user->barber_id;
        }

        $service = Service::find($data['service_id']);

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

        if (WalkIn::hasBarberConflict($data['barber_id'] ?? null, $data['visit_date'], $data['start_time'], $data['end_time'], $this->record->getKey())) {
            throw ValidationException::withMessages([
                'start_time' => 'This barber already has another appointment or walk-in during that time.',
            ]);
        }

        return $data;
    }
}
