<?php

namespace App\Filament\Resources\Orders\OrderStatusAutomationRuleResource\Pages;

use App\Filament\Resources\Orders\OrderStatusAutomationRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrderStatusAutomationRule extends EditRecord
{
    protected static string $resource = OrderStatusAutomationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
