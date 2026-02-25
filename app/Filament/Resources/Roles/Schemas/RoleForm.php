<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $labels = config('permissions.labels', []);
        $permissionNames = Permission::orderBy('name')->pluck('name');
        $options = $permissionNames->mapWithKeys(function (string $name) use ($labels) {
            $label = $labels[$name] ?? str_replace('-', ' ', ucfirst($name));

            return [$name => $label];
        })->toArray();

        return $schema->components([
            Section::make(__('Role'))
                ->description(__('Configure which permissions this role has. Changes apply immediately.'))
                ->schema([
                    Placeholder::make('name')
                        ->label(__('Role name'))
                        ->content(fn (?Role $record): string => $record?->name ?? '—'),

                    Placeholder::make('guard_name')
                        ->label(__('Guard'))
                        ->content(fn (?Role $record): string => $record?->guard_name ?? '—'),

                    CheckboxList::make('permission_names')
                        ->label(__('Permissions'))
                        ->options($options)
                        ->columns(2)
                        ->grid(2)
                        ->bulkToggleable(),
                ]),
        ]);
    }
}
