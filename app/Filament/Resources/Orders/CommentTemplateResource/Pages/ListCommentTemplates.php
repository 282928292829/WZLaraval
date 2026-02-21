<?php

namespace App\Filament\Resources\Orders\CommentTemplateResource\Pages;

use App\Filament\Resources\Orders\CommentTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommentTemplates extends ListRecords
{
    protected static string $resource = CommentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
