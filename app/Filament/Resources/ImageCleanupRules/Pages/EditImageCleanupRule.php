<?php

namespace App\Filament\Resources\ImageCleanupRules\Pages;

use App\Filament\Resources\ImageCleanupRules\ImageCleanupRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditImageCleanupRule extends EditRecord
{
    protected static string $resource = ImageCleanupRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
