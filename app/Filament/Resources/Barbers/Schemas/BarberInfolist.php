<?php

namespace App\Filament\Resources\Barbers\Schemas;

use App\Models\Barber;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BarberInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('image')
                    ->label('Profile Photo')
                    ->disk('public')
                    ->circular()
                    ->size(140)
                    ->columnSpanFull(),
                TextEntry::make('firstname'),
                TextEntry::make('middlename')
                    ->placeholder('-'),
                TextEntry::make('lastname'),
                TextEntry::make('contact_number')
                    ->placeholder('-'),
                IconEntry::make('is_available')
                    ->boolean(),
                TextEntry::make('weekly_schedule_summary')
                    ->label('Weekly Schedule')
                    ->state(fn (Barber $record): array => $record->weekly_schedule_summary)
                    ->listWithLineBreaks()
                    ->placeholder('No weekly schedule set yet.')
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
