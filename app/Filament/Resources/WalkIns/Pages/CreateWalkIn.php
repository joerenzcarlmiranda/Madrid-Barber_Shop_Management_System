<?php

namespace App\Filament\Resources\WalkIns\Pages;

use App\Filament\Concerns\InteractsWithAvailabilityNotifications;
use App\Filament\Resources\WalkIns\WalkInResource;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Models\WalkIn;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateWalkIn extends CreateRecord
{
    use InteractsWithAvailabilityNotifications;

    protected static string $resource = WalkInResource::class;

    protected function getAvailabilityNotificationId(): string
    {
        return 'walkin-availability';
    }

    protected function getAvailabilityNotificationTitle(): string
    {
        return 'Walk-in time unavailable';
    }

    protected function getCurrentAvailabilityStatus(): array
    {
        $service = filled($this->data['service_id'] ?? null)
            ? Service::find($this->data['service_id'])
            : null;

        return Appointment::getBarberAvailabilityStatus(
            $this->data['barber_id'] ?? null,
            $this->data['visit_date'] ?? null,
            $this->data['start_time'] ?? null,
            $service,
        );
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->disabled(fn (): bool => $this->hasInvalidAvailabilitySelection());
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->disabled(fn (): bool => $this->hasInvalidAvailabilitySelection());
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user instanceof User && $user->isBarber()) {
            $data['barber_id'] = $user->barber_id;
        }

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

        $availabilityStatus = Appointment::getBarberAvailabilityStatus(
            $data['barber_id'] ?? null,
            $data['visit_date'],
            $data['start_time'],
            $service,
            null,
            null,
        );

        if (($availabilityStatus['available'] ?? null) === false) {
            throw ValidationException::withMessages([
                'start_time' => $availabilityStatus['message'],
            ]);
        }

        if (WalkIn::hasBarberConflict($data['barber_id'] ?? null, $data['visit_date'], $data['start_time'], $data['end_time'])) {
            throw ValidationException::withMessages([
                'start_time' => 'This barber already has another appointment or walk-in during that time.',
            ]);
        }

        return $data;
    }
}
