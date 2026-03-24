<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerInfolist
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
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('phone_no')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
