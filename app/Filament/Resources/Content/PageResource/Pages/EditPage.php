<?php

namespace App\Filament\Resources\Content\PageResource\Pages;

use App\Filament\Resources\Content\PageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(__('View page'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn () => route('pages.show', $this->record->slug))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
