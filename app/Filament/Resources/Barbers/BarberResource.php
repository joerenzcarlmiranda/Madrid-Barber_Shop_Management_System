<?php

namespace App\Filament\Resources\Barbers;

use App\Filament\Resources\Barbers\Pages\CreateBarber;
use App\Filament\Resources\Barbers\Pages\EditBarber;
use App\Filament\Resources\Barbers\Pages\ListBarbers;
use App\Filament\Resources\Barbers\Pages\ViewBarber;
use App\Filament\Resources\Barbers\Schemas\BarberForm;
use App\Filament\Resources\Barbers\Schemas\BarberInfolist;
use App\Filament\Resources\Barbers\Tables\BarbersTable;
use App\Models\Barber;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BarberResource extends Resource
{
    protected static ?string $model = Barber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScissors;

    public static function form(Schema $schema): Schema
    {
        return BarberForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BarberInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BarbersTable::configure($table);
    }

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isBarber() ? 'My Profile' : 'Barbers';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin() || $user->isCustomer()) {
            return $query;
        }

        if ($user->isBarber()) {
            return $query->whereKey($user->barber_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBarbers::route('/'),
            'create' => CreateBarber::route('/create'),
            'view' => ViewBarber::route('/{record}'),
            'edit' => EditBarber::route('/{record}/edit'),
        ];
    }
}
