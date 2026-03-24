<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Support\LinkedUserAccountManager;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected array $accountData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->accountData = Arr::only($data, ['password']);

        return Arr::except($data, ['password', 'password_confirmation']);
    }

    protected function afterCreate(): void
    {
        app(LinkedUserAccountManager::class)->syncForCustomer(
            $this->record,
            $this->accountData['password'] ?? null,
        );
    }
}
