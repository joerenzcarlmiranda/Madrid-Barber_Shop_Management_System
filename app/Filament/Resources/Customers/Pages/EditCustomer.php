<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Support\LinkedUserAccountManager;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected array $accountData = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->accountData = Arr::only($data, ['password']);

        return Arr::except($data, ['password', 'password_confirmation']);
    }

    protected function afterSave(): void
    {
        app(LinkedUserAccountManager::class)->syncForCustomer(
            $this->record,
            $this->accountData['password'] ?? null,
        );
    }
}
