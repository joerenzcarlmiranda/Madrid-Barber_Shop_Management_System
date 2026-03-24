<?php

namespace App\Filament\Resources\Barbers\Pages;

use App\Filament\Resources\Barbers\BarberResource;
use App\Support\LinkedUserAccountManager;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditBarber extends EditRecord
{
    protected static string $resource = BarberResource::class;

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
        app(LinkedUserAccountManager::class)->syncForBarber(
            $this->record,
            $this->accountData['password'] ?? null,
        );
    }
}
