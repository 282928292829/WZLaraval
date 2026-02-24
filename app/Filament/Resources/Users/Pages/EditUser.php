<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Password;
use STS\FilamentImpersonate\Actions\Impersonate;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()
                ->record($this->getRecord())
                ->label(__('Impersonate user')),

            Action::make('sendPasswordReset')
                ->label(__('Send password reset'))
                ->icon('heroicon-o-key')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading(__('Send password reset email?'))
                ->modalDescription(__('A password reset link will be sent to :email.', ['email' => $this->getRecord()->email]))
                ->action(function () {
                    $user = $this->getRecord();
                    $status = Password::sendResetLink(['email' => $user->email]);

                    if ($status === Password::RESET_LINK_SENT) {
                        Notification::make()
                            ->title(__('Password reset link sent'))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('Unable to send password reset'))
                            ->danger()
                            ->send();
                    }
                }),

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
