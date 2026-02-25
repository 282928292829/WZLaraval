<?php

namespace App\Filament\Resources\Orders\CommentTemplateResource\Pages;

use App\Filament\Resources\Orders\CommentTemplateResource;
use App\Services\ImportCommentTemplatesFromWordPress;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCommentTemplates extends ListRecords
{
    protected static string $resource = CommentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importFromWordPress')
                ->label(__('Import from WordPress'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading(__('Import Comment Templates from WordPress'))
                ->modalDescription(__('comment_templates.import_modal_description'))
                ->modalSubmitActionLabel(__('Import'))
                ->action(function (): void {
                    $result = app(ImportCommentTemplatesFromWordPress::class)->import(replaceExisting: true);

                    if ($result['success']) {
                        Notification::make()
                            ->title($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title($result['message'])
                            ->danger()
                            ->send();
                    }
                }),

            CreateAction::make(),
        ];
    }
}
