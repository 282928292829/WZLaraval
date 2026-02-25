<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Role'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (Role $record): string => match ($record->name) {
                        'superadmin' => 'danger',
                        'admin' => 'warning',
                        'editor' => 'info',
                        'customer' => 'success',
                        'guest' => 'gray',
                        default => 'primary',
                    }),

                TextColumn::make('guard_name')
                    ->label(__('Guard'))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('permissions_count')
                    ->label(__('Permissions'))
                    ->counts('permissions')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }
}
