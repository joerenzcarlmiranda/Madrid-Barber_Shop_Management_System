<?php

namespace App\Filament\Resources\Barbers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BarberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('firstname')
                    ->required(),
                TextInput::make('middlename')
                    ->default(null),
                TextInput::make('lastname')
                    ->required(),
                TextInput::make('contact_number')
                    ->default(null),
                Toggle::make('is_available')
                    ->required(),
            ]);
    }
}
