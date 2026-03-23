<?php

namespace App\Filament\Resources\WalkIns\Schemas;

use App\Models\Barber;
use App\Models\Customer;
use App\Models\Service;
use App\Models\WalkIn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

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
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateEndTime($get, $set))
                    ->native(false)
                    ->required(),
                Select::make('barber_id')
                    ->relationship('barber', 'firstname')
                    ->getOptionLabelFromRecordUsing(fn (Barber $record): string => $record->full_name)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->label('Barber'),
                DatePicker::make('visit_date')
                    ->default(now()->toDateString())
                    ->required(),
                TimePicker::make('arrival_time')
                    ->seconds(false)
                    ->default(now()->format('H:i'))
                    ->required(),
                TimePicker::make('start_time')
                    ->seconds(false)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateEndTime($get, $set)),
                TimePicker::make('end_time')
                    ->seconds(false)
                    ->readOnly()
                    ->helperText('Automatically computed from the selected service duration once start time is set.')
                    ->dehydrated(),
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
}
