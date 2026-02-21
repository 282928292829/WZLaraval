<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(function () {
                    /** @var \App\Models\User|null $user */
                    $user = auth()->user();

                    return $user?->hasPermissionTo('manage-users') ?? false;
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove null password so it doesn't overwrite with blank
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Sync direct permission overrides from the checkbox list state
        $directPermissions = $this->data['direct_permissions'] ?? [];
        $record->syncPermissions($directPermissions);

        Notification::make()
            ->title(__('User saved'))
            ->success()
            ->send();
    }
}
