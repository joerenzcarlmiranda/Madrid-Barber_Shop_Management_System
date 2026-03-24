<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Concerns\InteractsWithAvailabilityNotifications;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAppointment extends CreateRecord
{
    use InteractsWithAvailabilityNotifications;

    protected static string $resource = AppointmentResource::class;

    protected function getAvailabilityNotificationId(): string
    {
        return 'appointment-availability';
    }

    protected function getCurrentAvailabilityStatus(): array
    {
        $service = filled($this->data['service_id'] ?? null)
            ? Service::find($this->data['service_id'])
            : null;

        return Appointment::getBarberAvailabilityStatus(
            $this->data['barber_id'] ?? null,
            $this->data['appointment_date'] ?? null,
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

        if ($user instanceof User) {
            if ($user->isCustomer()) {
                $data['customer_id'] = $user->customer_id;
                $data['status'] = 'pending';
            }

            if ($user->isBarber()) {
                $data['barber_id'] = $user->barber_id;
            }
        }

        $service = Service::find($data['service_id']);
        $calculatedEndTime = Appointment::calculateEndTime($data['start_time'] ?? null, $service);

        if (blank($calculatedEndTime)) {
            throw ValidationException::withMessages([
                'service_id' => 'The selected service has an invalid duration.',
            ]);
        }

        $startTime = Carbon::parse($data['start_time'])->format('H:i:s');
        $endTime = Carbon::parse($calculatedEndTime)->format('H:i:s');

        $data['start_time'] = $startTime;
        $data['end_time'] = $endTime;

        $availabilityStatus = Appointment::getBarberAvailabilityStatus(
            $data['barber_id'] ?? null,
            $data['appointment_date'],
            $startTime,
            $service,
        );

        if (($availabilityStatus['available'] ?? null) === false) {
            throw ValidationException::withMessages([
                'start_time' => $availabilityStatus['message'],
            ]);
        }

        return $data;
    }
}
