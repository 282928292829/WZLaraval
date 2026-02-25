<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $record */
        $record = $this->getRecord();
        $data['permission_names'] = $record->permissions->pluck('name')->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['permission_names']);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $permissionNames = $this->form->getState()['permission_names'] ?? [];
        $record->syncPermissions($permissionNames);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Notification::make()
            ->title(__('Role updated'))
            ->success()
            ->send();
    }
}
