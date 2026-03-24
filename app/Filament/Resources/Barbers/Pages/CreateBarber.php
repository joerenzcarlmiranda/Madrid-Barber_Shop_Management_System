<?php

namespace App\Filament\Resources\Barbers\Pages;

use App\Filament\Resources\Barbers\BarberResource;
use App\Support\LinkedUserAccountManager;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateBarber extends CreateRecord
{
    protected static string $resource = BarberResource::class;

    protected array $accountData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->accountData = Arr::only($data, ['password']);

        return Arr::except($data, ['password', 'password_confirmation']);
    }

    protected function afterCreate(): void
    {
        app(LinkedUserAccountManager::class)->syncForBarber(
            $this->record,
            $this->accountData['password'] ?? null,
        );
    }
}
