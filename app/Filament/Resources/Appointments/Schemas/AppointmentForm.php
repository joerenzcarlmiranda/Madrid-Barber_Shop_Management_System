<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Service;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'firstname')
                    ->getOptionLabelFromRecordUsing(fn (Customer $record): string => $record->full_name)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->label('Customer')
                    ->required(),
                Select::make('service_id')
                    ->relationship('service', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Service $record): string => $record->name)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateEndTime($get, $set))
                    ->label('Service')
                    ->required()
                    ->native(false),
                Select::make('barber_id')
                    ->relationship('barber', 'firstname')
                    ->getOptionLabelFromRecordUsing(fn (Barber $record): string => $record->full_name)
                    ->searchable()
                    ->preload()
                    ->label('Barber')
                    ->required()
                    ->native(false),
                DatePicker::make('appointment_date')
                    ->required(),
                TimePicker::make('start_time')
                    ->seconds(false)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateEndTime($get, $set))
                    ->required(),
                TimePicker::make('end_time')
                    ->seconds(false)
                    ->readOnly()
                    ->helperText('Automatically computed from the selected service duration.')
                    ->dehydrated()
                    ->required(),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ])
                    ->default('pending')
                    ->required(),
                Textarea::make('notes')
                    ->default(null)
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

        $set('end_time', Appointment::calculateEndTime($startTime, $service));
    }
}
