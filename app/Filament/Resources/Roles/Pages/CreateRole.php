<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public function form(Schema $schema): Schema
    {
        return RoleForm::configureForCreate($schema);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['permission_names']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Role $record */
        $record = $this->getRecord();
        $permissionNames = $this->form->getState()['permission_names'] ?? [];
        $record->syncPermissions($permissionNames);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Notification::make()
            ->title(__('roles.created'))
            ->success()
            ->send();
    }
}
