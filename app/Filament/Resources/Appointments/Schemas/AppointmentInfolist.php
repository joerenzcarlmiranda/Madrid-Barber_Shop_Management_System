<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\Appointment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer.firstname')
                    ->label('Customer')
                    ->formatStateUsing(fn (?string $state, Appointment $record): string => $record->customer?->full_name ?? '-'),
                TextEntry::make('service.name')
                    ->label('Service'),
                TextEntry::make('barber.firstname')
                    ->label('Barber')
                    ->formatStateUsing(fn (?string $state, Appointment $record): string => $record->barber?->full_name ?? '-'),
                TextEntry::make('appointment_date')
                    ->date(),
                TextEntry::make('start_time')
                    ->time(),
                TextEntry::make('end_time')
                    ->time(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
