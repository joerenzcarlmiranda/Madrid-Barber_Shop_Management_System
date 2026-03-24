<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image')
                    ->label('Profile Photo')
                    ->image()
                    ->avatar()
                    ->circleCropper()
                    ->disk('public')
                    ->directory('customers')
                    ->visibility('public')
                    ->imageEditor()
                    ->imageEditorAspectRatios(['1:1'])
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(),
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
                        ignorable: fn (?Customer $record) => $record?->user,
                        ignoreRecord: false,
                    )
                    ->helperText('This email will also be used as the customer login.'),
                TextInput::make('phone_no')
                    ->tel()
                    ->default(null),
                Section::make('Portal Access')
                    ->description('Set the password used by this customer to sign in to the Filament panel.')
                    ->components([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (?Customer $record) => blank($record?->user))
                            ->same('password_confirmation')
                            ->helperText(
                                fn (?Customer $record) => $record?->user
                                    ? 'Leave blank to keep the current password.'
                                    : 'Set the initial password for this customer account.',
                            ),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->revealable()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
