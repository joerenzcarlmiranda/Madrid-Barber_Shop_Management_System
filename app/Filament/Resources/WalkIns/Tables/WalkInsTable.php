<?php

namespace App\Filament\Resources\WalkIns\Tables;

use App\Models\WalkIn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalkInsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('visit_date', 'desc')
            ->columns([
                TextColumn::make('queue_number')
                    ->searchable(),
                TextColumn::make('customer.firstname')
                    ->label('Customer')
                    ->formatStateUsing(fn (?string $state, WalkIn $record): string => $record->display_customer_name)
                    ->searchable(['guest_name']),
                TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable(),
                TextColumn::make('barber.firstname')
                    ->label('Barber')
                    ->formatStateUsing(fn (?string $state, WalkIn $record): string => $record->barber?->full_name ?? 'Unassigned')
                    ->searchable(['barber.firstname', 'barber.lastname']),
                TextColumn::make('visit_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('arrival_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->time()
                    ->placeholder('-'),
                TextColumn::make('end_time')
                    ->time()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
