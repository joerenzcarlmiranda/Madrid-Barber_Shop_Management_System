<?php

namespace App\Filament\Resources\Barbers\Schemas;

use App\Models\Barber;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->unique(
                        table: User::class,
                        column: 'email',
                        ignorable: fn (?Barber $record) => $record?->user,
                        ignoreRecord: false,
                    )
                    ->helperText('This email will also be used as the barber login.'),
                TextInput::make('contact_number')
                    ->default(null),
                Toggle::make('is_available')
                    ->required(),
                Section::make('Portal Access')
                    ->description('Set the password used by this barber to sign in to the Filament panel.')
                    ->components([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (?Barber $record) => blank($record?->user))
                            ->same('password_confirmation')
                            ->helperText(
                                fn (?Barber $record) => $record?->user
                                    ? 'Leave blank to keep the current password.'
                                    : 'Set the initial password for this barber account.',
                            ),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->revealable()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
