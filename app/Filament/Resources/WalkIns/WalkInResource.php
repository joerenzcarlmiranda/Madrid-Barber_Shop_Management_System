<?php

namespace App\Filament\Resources\WalkIns;

use App\Filament\Resources\WalkIns\Pages\CreateWalkIn;
use App\Filament\Resources\WalkIns\Pages\EditWalkIn;
use App\Filament\Resources\WalkIns\Pages\ListWalkIns;
use App\Filament\Resources\WalkIns\Pages\ViewWalkIn;
use App\Filament\Resources\WalkIns\Schemas\WalkInForm;
use App\Filament\Resources\WalkIns\Schemas\WalkInInfolist;
use App\Filament\Resources\WalkIns\Tables\WalkInsTable;
use App\Models\WalkIn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WalkInResource extends Resource
{
    protected static ?string $model = WalkIn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    public static function form(Schema $schema): Schema
    {
        return WalkInForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WalkInInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WalkInsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWalkIns::route('/'),
            'create' => CreateWalkIn::route('/create'),
            'view' => ViewWalkIn::route('/{record}'),
            'edit' => EditWalkIn::route('/{record}/edit'),
        ];
    }
}
