<?php

namespace App\Filament\Resources\Orders\CommentTemplateResource\Pages;

use App\Filament\Resources\Orders\CommentTemplateResource;
use App\Services\ImportCommentTemplatesFromCsv;
use App\Services\ImportCommentTemplatesFromWordPress;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCommentTemplates extends ListRecords
{
    protected static string $resource = CommentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('Export CSV'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(route('admin.comment-templates.export-csv'))
                ->openUrlInNewTab(),

            Action::make('importFromCsv')
                ->label(__('Import CSV'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    FileUpload::make('csv_file')
                        ->label(__('comment_templates.csv_file'))
                        ->required()
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                        ->maxSize(5120)
                        ->helperText(__('comment_templates.csv_import_help'))
                        ->directory('temp/csv-imports'),
                    Checkbox::make('replace_existing')
                        ->label(__('comment_templates.csv_replace_existing'))
                        ->helperText(__('comment_templates.csv_replace_help'))
                        ->default(false),
                ])
                ->modalHeading(__('Import Comment Templates from CSV'))
                ->modalSubmitActionLabel(__('Import'))
                ->action(function (array $data): void {
                    $result = app(ImportCommentTemplatesFromCsv::class)->import(
                        $data['csv_file'],
                        replaceExisting: (bool) ($data['replace_existing'] ?? false)
                    );

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
