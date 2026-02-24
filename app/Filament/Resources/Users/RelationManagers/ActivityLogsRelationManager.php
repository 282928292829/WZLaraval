<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\UserActivityLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Activity History');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('event')
                    ->label(__('Event'))
                    ->formatStateUsing(fn (UserActivityLog $record) => $record->eventLabel())
                    ->badge()
                    ->color(fn (UserActivityLog $record) => match ($record->eventColor()) {
                        'green' => 'success',
                        'red' => 'danger',
                        'orange' => 'warning',
                        'blue', 'indigo', 'teal' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('ip_address')
                    ->label(__('IP'))
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('city')
                    ->label(__('City'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('country')
                    ->label(__('Country'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('device')
                    ->label(__('Device'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('os')
                    ->label(__('OS'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('browser')
                    ->label(__('Browser'))
                    ->formatStateUsing(fn (?string $state, UserActivityLog $record) => $state
                        ? ($record->browser_version ? "{$state} {$record->browser_version}" : $state)
                        : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([])
            ->recordActions([])
            ->paginated([10, 25, 50]);
    }
}
