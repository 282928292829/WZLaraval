<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleForm
{
    /** Permission options for both create and edit. */
    public static function permissionOptions(): array
    {
        $labels = config('permissions.labels', []);
        $permissionNames = Permission::orderBy('name')->pluck('name');

        return $permissionNames->mapWithKeys(function (string $name) use ($labels) {
            $label = $labels[$name] ?? str_replace('-', ' ', ucfirst($name));

            return [$name => $label];
        })->toArray();
    }

    /** Form schema for creating a new role (editable name and guard). */
    public static function configureForCreate(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('roles.section_create'))
                ->description(__('roles.section_create_description'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('Role name'))
                        ->required()
                        ->maxLength(255)
                        ->rules([
                            Rule::unique('roles', 'name'),
                        ]),

                    Select::make('guard_name')
                        ->label(__('Guard'))
                        ->options(['web' => 'web'])
                        ->default('web')
                        ->required(),

                    CheckboxList::make('permission_names')
                        ->label(__('Permissions'))
                        ->options(static::permissionOptions())
                        ->columns(2)
                        ->bulkToggleable(),
                ]),
        ]);
    }

    /** Form schema for editing an existing role (name and guard read-only). */
    public static function configure(Schema $schema): Schema
    {
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
                        ->options(static::permissionOptions())
                        ->columns(2)
                        ->bulkToggleable(),
                ]),
        ]);
    }
}
