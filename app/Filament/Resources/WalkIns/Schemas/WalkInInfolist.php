<?php

namespace App\Filament\Resources\WalkIns\Schemas;

use App\Models\WalkIn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WalkInInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('queue_number'),
                TextEntry::make('customer.firstname')
                    ->label('Customer')
                    ->formatStateUsing(fn (?string $state, WalkIn $record): string => $record->display_customer_name),
                TextEntry::make('service.name')
                    ->label('Service'),
                TextEntry::make('barber.firstname')
                    ->label('Barber')
                    ->formatStateUsing(fn (?string $state, WalkIn $record): string => $record->barber?->full_name ?? '-'),
                TextEntry::make('visit_date')
                    ->date(),
                TextEntry::make('arrival_time')
                    ->time(),
                TextEntry::make('start_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('end_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}
