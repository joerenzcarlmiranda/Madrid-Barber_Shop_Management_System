<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;

trait InteractsWithAvailabilityNotifications
{
    public function notifyCurrentAvailabilityStatus(): void
    {
        $status = $this->getCurrentAvailabilityStatus();
        $notificationId = $this->getAvailabilityNotificationId();

        $this->dispatch('close-notification', id: $notificationId);

        if (($status['available'] ?? null) !== false) {
            return;
        }

        Notification::make($notificationId)
            ->danger()
            ->title($this->getAvailabilityNotificationTitle())
            ->body($status['message'] ?? 'The selected time is not available.')
            ->persistent()
            ->send();
    }

    protected function hasInvalidAvailabilitySelection(): bool
    {
        return ($this->getCurrentAvailabilityStatus()['available'] ?? null) === false;
    }

    protected function getAvailabilityNotificationTitle(): string
    {
        return 'Time unavailable';
    }

    abstract protected function getAvailabilityNotificationId(): string;

    /**
     * @return array{available: bool|null, message: string|null, end_time?: string|null}
     */
    abstract protected function getCurrentAvailabilityStatus(): array;
}
