<?php

namespace App\Filament\Resources\WalkIns\Schemas;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\WalkIn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class WalkInForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('queue_number')
                    ->readOnly()
                    ->dehydrated(false)
                    ->placeholder('Generated automatically'),
                Select::make('customer_id')
                    ->relationship('customer', 'firstname')
                    ->getOptionLabelFromRecordUsing(fn (Customer $record): string => $record->full_name)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->label('Customer'),
                TextInput::make('guest_name')
                    ->label('Guest Name')
                    ->placeholder('Use this for walk-in guests without a customer record'),
                Select::make('service_id')
                    ->relationship('service', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Service $record): string => $record->name)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set, mixed $livewire) => self::refreshAvailabilityFeedback($get, $set, $livewire))
                    ->native(false)
                    ->required(),
                Select::make('barber_id')
                    ->relationship(
                        'barber',
                        'firstname',
                        modifyQueryUsing: fn (Builder $query) => self::restrictBarbersForCurrentUser($query),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Barber $record): string => $record->full_name)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (mixed $livewire) => self::triggerAvailabilityNotification($livewire))
                    ->native(false)
                    ->label('Barber')
                    ->default(fn (): ?int => auth()->user()?->barber_id)
                    ->disabled(fn (): bool => auth()->user()?->isBarber() ?? false),
                DatePicker::make('visit_date')
                    ->default(now()->toDateString())
                    ->live()
                    ->afterStateUpdated(fn (mixed $livewire) => self::triggerAvailabilityNotification($livewire))
                    ->required(),
                TimePicker::make('arrival_time')
                    ->seconds(false)
                    ->default(now()->format('H:i'))
                    ->required(),
                TimePicker::make('start_time')
                    ->seconds(false)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set, mixed $livewire) => self::refreshAvailabilityFeedback($get, $set, $livewire)),
                TimePicker::make('end_time')
                    ->seconds(false)
                    ->readOnly()
                    ->helperText('Automatically computed from the selected service duration once start time is set.')
                    ->dehydrated(),
                Placeholder::make('availability_feedback')
                    ->label('Availability')
                    ->content(fn (Get $get) => self::getAvailabilityFeedback($get))
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        'waiting' => 'Waiting',
                        'called' => 'Called',
                        'in_service' => 'In Service',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('waiting')
                    ->native(false)
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    protected static function updateEndTime(Get $get, Set $set): void
    {
        $startTime = $get('start_time');
        $serviceId = $get('service_id');

        if (blank($startTime) || blank($serviceId)) {
            $set('end_time', null);

            return;
        }

        $service = Service::find($serviceId);

        $set('end_time', WalkIn::calculateEndTime($startTime, $service));
    }

    protected static function refreshAvailabilityFeedback(Get $get, Set $set, mixed $livewire): void
    {
        self::updateEndTime($get, $set);
        self::triggerAvailabilityNotification($livewire);
    }

    protected static function restrictBarbersForCurrentUser(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user instanceof User && $user->isBarber()) {
            return $query->whereKey($user->barber_id);
        }

        return $query;
    }

    protected static function getAvailabilityFeedback(Get $get): HtmlString
    {
        $serviceId = $get('service_id');
        $service = filled($serviceId) ? Service::find($serviceId) : null;

        $status = Appointment::getBarberAvailabilityStatus(
            $get('barber_id'),
            $get('visit_date'),
            $get('start_time'),
            $service,
        );

        $message = e($status['message'] ?? 'Select the service, barber, date, and start time to check availability.');

        if (($status['available'] ?? null) === true) {
            return new HtmlString("<span class='text-success-600 dark:text-success-400'>{$message}</span>");
        }

        if (($status['available'] ?? null) === false) {
            return new HtmlString("<span class='text-danger-600 dark:text-danger-400'>{$message}</span>");
        }

        return new HtmlString("<span class='text-gray-500 dark:text-gray-400'>{$message}</span>");
    }

    protected static function triggerAvailabilityNotification(mixed $livewire): void
    {
        if (method_exists($livewire, 'notifyCurrentAvailabilityStatus')) {
            $livewire->notifyCurrentAvailabilityStatus();
        }
    }
}
