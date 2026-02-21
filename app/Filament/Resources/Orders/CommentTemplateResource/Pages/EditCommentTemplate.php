<?php

namespace App\Filament\Resources\Orders\CommentTemplateResource\Pages;

use App\Filament\Resources\Orders\CommentTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommentTemplate extends EditRecord
{
    protected static string $resource = CommentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
