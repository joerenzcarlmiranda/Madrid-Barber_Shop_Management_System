<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

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

        $exists = Appointment::where('barber_id', $data['barber_id'])
            ->where('appointment_date', $data['appointment_date'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($nestedQuery) use ($startTime, $endTime) {
                        $nestedQuery->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'start_time' => 'This time slot is already booked.',
            ]);
        }

        return $data;
    }
}
