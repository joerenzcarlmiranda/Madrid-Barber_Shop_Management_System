<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

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

        if ($user instanceof User) {
            if ($user->isCustomer()) {
                $data['customer_id'] = $user->customer_id;
                $data['status'] = $this->record->status;
            }

            if ($user->isBarber()) {
                $data['barber_id'] = $user->barber_id;
            }
        }

        return $data;
    }
}
