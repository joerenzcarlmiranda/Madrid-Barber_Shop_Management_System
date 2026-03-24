<?php

namespace App\Filament\Resources\Barbers\Schemas;

use App\Models\Barber;
use App\Models\BarberSchedule;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BarberForm
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
                    ->directory('barbers')
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
                        ignorable: fn (?Barber $record) => $record?->user,
                        ignoreRecord: false,
                    )
                    ->helperText('This email will also be used as the barber login.'),
                TextInput::make('contact_number')
                    ->default(null),
                Toggle::make('is_available')
                    ->required(),
                Section::make('Weekly Schedule')
                    ->description('Set the barber working hours for each day. Times outside this schedule will be rejected during appointment booking.')
                    ->components([
                        Repeater::make('schedules')
                            ->relationship(
                                'schedules',
                                modifyQueryUsing: fn ($query) => $query->orderBy('day_of_week'),
                            )
                            ->schema([
                                Select::make('day_of_week')
                                    ->label('Day')
                                    ->options(BarberSchedule::dayOptions())
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->required()
                                    ->native(false),
                                Toggle::make('is_day_off')
                                    ->label('Day Off')
                                    ->live(),
                                TimePicker::make('start_time')
                                    ->seconds(false)
                                    ->required(fn (Get $get): bool => ! ((bool) $get('is_day_off')))
                                    ->disabled(fn (Get $get): bool => (bool) $get('is_day_off')),
                                TimePicker::make('end_time')
                                    ->seconds(false)
                                    ->after('start_time')
                                    ->required(fn (Get $get): bool => ! ((bool) $get('is_day_off')))
                                    ->disabled(fn (Get $get): bool => (bool) $get('is_day_off')),
                            ])
                            ->columns(4)
                            ->columnSpanFull()
                            ->addActionLabel('Add Working Day')
                            ->defaultItems(0)
                            ->maxItems(7)
                            ->reorderable(false)
                            ->itemLabel(
                                fn (array $state): ?string => filled($state['day_of_week'] ?? null)
                                    ? (BarberSchedule::dayOptions()[(int) $state['day_of_week']] ?? null)
                                    : null,
                            )
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => self::normalizeScheduleData($data))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => self::normalizeScheduleData($data)),
                    ]),
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

    protected static function normalizeScheduleData(array $data): array
    {
        if ($data['is_day_off'] ?? false) {
            $data['start_time'] = null;
            $data['end_time'] = null;

            return $data;
        }

        if (filled($data['start_time'] ?? null)) {
            $data['start_time'] = date('H:i:s', strtotime((string) $data['start_time']));
        }

        if (filled($data['end_time'] ?? null)) {
            $data['end_time'] = date('H:i:s', strtotime((string) $data['end_time']));
        }

        return $data;
    }
}
